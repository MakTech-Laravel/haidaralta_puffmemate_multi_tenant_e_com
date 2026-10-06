<?php

namespace App\Services\Order;

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\OtpPurpose;
use App\Models\DeliveryAttempt;
use App\Models\Inventory;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\OtpService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class DriverDeliveryService
{
    public const HANDOVER_TTL_MINUTES = 120;

    public function __construct(
        private FulfillmentStatusService $statuses,
        private PaymentMarkingService $payments,
        private InventoryReservationService $reservations,
        private OtpService $otp,
    ) {}

    public function pickUp(OrderFulfillment $fulfillment, User $driver): OrderFulfillment
    {
        $this->assertAssignedDriver($fulfillment, $driver);

        if ($fulfillment->status !== FulfillmentStatus::Assigned) {
            throw ValidationException::withMessages([
                'status' => 'This order is not waiting for pickup.',
            ]);
        }

        return $this->statuses->apply($fulfillment, FulfillmentStatus::PickedUp, $driver, 'Picked up');
    }

    public function startDelivery(OrderFulfillment $fulfillment, User $driver): OrderFulfillment
    {
        $this->assertAssignedDriver($fulfillment, $driver);

        if ($fulfillment->status !== FulfillmentStatus::PickedUp && $fulfillment->status !== FulfillmentStatus::DeliveryFailed) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot start delivery.',
            ]);
        }

        $phone = $this->phone($fulfillment);
        $this->otp->issue($phone, OtpPurpose::DeliveryHandover, $fulfillment, self::HANDOVER_TTL_MINUTES);

        return $this->statuses->apply($fulfillment, FulfillmentStatus::OutForDelivery, $driver, 'Out for delivery');
    }

    public function complete(OrderFulfillment $fulfillment, User $driver, string $code): OrderFulfillment
    {
        $this->assertAssignedDriver($fulfillment, $driver);

        if ($fulfillment->status !== FulfillmentStatus::OutForDelivery) {
            throw ValidationException::withMessages([
                'otp' => 'This order is not out for delivery.',
            ]);
        }

        $this->otp->verify($this->phone($fulfillment), OtpPurpose::DeliveryHandover, $code, $fulfillment);

        return DB::transaction(function () use ($fulfillment, $driver): OrderFulfillment {
            $this->payments->collect($fulfillment->order, $driver);
            $fulfillment->forceFill(['paid_at' => now()])->save();
            $this->saleReserved($fulfillment, $driver);

            return $this->statuses->apply($fulfillment->refresh(), FulfillmentStatus::Delivered, $driver, 'Delivered');
        });
    }

    public function fail(
        OrderFulfillment $fulfillment,
        User $driver,
        DeliveryFailureReason $reason,
        ?string $note = null,
    ): OrderFulfillment {
        $this->assertAssignedDriver($fulfillment, $driver);

        if ($fulfillment->status !== FulfillmentStatus::OutForDelivery) {
            throw ValidationException::withMessages([
                'status' => 'Only an active delivery can be marked unsuccessful.',
            ]);
        }

        DeliveryAttempt::query()->create([
            'fulfillment_id' => $fulfillment->id,
            'driver_id' => $driver->id,
            'reason' => $reason,
            'note' => $note,
            'attempted_at' => now(),
        ]);

        return $this->statuses->apply($fulfillment, FulfillmentStatus::DeliveryFailed, $driver, 'Delivery unsuccessful');
    }

    public function retry(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        if ($fulfillment->status !== FulfillmentStatus::DeliveryFailed) {
            throw ValidationException::withMessages([
                'status' => 'Retry is only available after a failed delivery.',
            ]);
        }

        $driver = $fulfillment->driver;

        if ($driver === null) {
            throw ValidationException::withMessages([
                'status' => 'Assign a driver before retrying delivery.',
            ]);
        }

        return $this->startDelivery($fulfillment, $driver);
    }

    public function reschedule(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        if ($fulfillment->status !== FulfillmentStatus::DeliveryFailed) {
            throw ValidationException::withMessages([
                'status' => 'Reschedule is only available after a failed delivery.',
            ]);
        }

        $fulfillment->forceFill(['driver_id' => null])->save();

        return $this->statuses->apply($fulfillment, FulfillmentStatus::Ready, $actor, 'Delivery rescheduled');
    }

    public function beginReturn(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        if ($fulfillment->status !== FulfillmentStatus::DeliveryFailed) {
            throw ValidationException::withMessages([
                'status' => 'Return is only available after a failed delivery.',
            ]);
        }

        return $this->statuses->apply($fulfillment, FulfillmentStatus::Returning, $actor, 'Returning to branch');
    }

    public function confirmReturn(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        if ($fulfillment->status !== FulfillmentStatus::Returning) {
            throw ValidationException::withMessages([
                'status' => 'Confirm return after the driver brings the goods back.',
            ]);
        }

        return $this->statuses->cancel($fulfillment, $actor, 'Returned to branch');
    }

    private function saleReserved(OrderFulfillment $fulfillment, User $actor): void
    {
        $order = $fulfillment->order()->with('items')->first();

        if ($order === null) {
            return;
        }

        $ids = [];
        $targets = [];

        foreach ($order->items as $item) {
            $query = Inventory::query()
                ->where('branch_id', $fulfillment->branch_id)
                ->where('product_id', $item->product_id);

            if ($item->variant_id === null) {
                $query->whereNull('variant_id');
            } else {
                $query->where('variant_id', $item->variant_id);
            }

            $inventory = $query->first();

            if ($inventory === null) {
                continue;
            }

            $ids[] = $inventory->id;
            $targets[] = [$inventory->id, $item->quantity];
        }

        $locked = $this->reservations->lockOrdered($ids);

        foreach ($targets as [$id, $quantity]) {
            $this->reservations->sale($locked[$id], $quantity, $order, $actor);
        }
    }

    private function assertAssignedDriver(OrderFulfillment $fulfillment, User $driver): void
    {
        if ($fulfillment->driver_id !== $driver->id) {
            abort(404);
        }
    }

    private function phone(OrderFulfillment $fulfillment): string
    {
        $phone = $fulfillment->order?->shipping_address_snapshot['phone'] ?? null;

        if (! is_string($phone) || $phone === '') {
            throw ValidationException::withMessages([
                'otp' => 'This order has no delivery phone number.',
            ]);
        }

        return $phone;
    }
}
