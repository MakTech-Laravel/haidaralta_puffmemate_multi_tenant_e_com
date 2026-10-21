<?php

namespace App\Services\Order;

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\HandoverVerification;
use App\Enums\NotificationType;
use App\Enums\OtpPurpose;
use App\Models\DeliveryAttempt;
use App\Models\Inventory;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Notifications\NotificationService;
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
        private NotificationService $notifications,
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

        if ($fulfillment->status !== FulfillmentStatus::PickedUp) {
            throw ValidationException::withMessages([
                'status' => 'This order cannot start delivery.',
            ]);
        }

        return $this->statuses->apply($fulfillment, FulfillmentStatus::OutForDelivery, $driver, 'Out for delivery');
    }

    public function arrive(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        $this->assertHandoverActor($fulfillment, $actor);

        if ($fulfillment->status !== FulfillmentStatus::OutForDelivery) {
            throw ValidationException::withMessages([
                'status' => 'Mark arrived only while the order is on the way.',
            ]);
        }

        return $this->statuses->apply($fulfillment, FulfillmentStatus::Arrived, $actor, 'Arrived');
    }

    public function complete(OrderFulfillment $fulfillment, User $actor, string $code): OrderFulfillment
    {
        $this->assertHandoverActor($fulfillment, $actor);

        if ($fulfillment->status !== FulfillmentStatus::Arrived) {
            throw ValidationException::withMessages([
                'otp' => 'Confirm the code after the order is marked arrived.',
            ]);
        }

        $order = $fulfillment->order;

        if ($order === null) {
            throw ValidationException::withMessages([
                'otp' => 'This order has no delivery phone number.',
            ]);
        }

        $phone = app(OrderPlacementNotifier::class)->phone($order);

        if ($phone === null) {
            throw ValidationException::withMessages([
                'otp' => 'This order has no delivery phone number.',
            ]);
        }

        try {
            $this->otp->verify($phone, OtpPurpose::OrderPlaced, $code, $order);
        } catch (ValidationException $exception) {
            throw ValidationException::withMessages([
                'otp' => $exception->errors()['code'] ?? $exception->getMessage(),
            ]);
        }

        return $this->finalize($fulfillment, $actor, $actor, 'Delivered');
    }

    /**
     * Staff override for a customer who cannot produce the code. The driver at
     * the door still collected the payment, so they stay the collector.
     */
    public function completeWithoutCode(
        OrderFulfillment $fulfillment,
        User $staff,
        HandoverVerification $method,
        string $note,
    ): OrderFulfillment {
        $this->assertHandoverActor($fulfillment, $staff);

        if ($staff->isDriver()) {
            abort(403);
        }

        if ($fulfillment->status !== FulfillmentStatus::Arrived) {
            throw ValidationException::withMessages([
                'status' => 'Complete without a code only after the driver has arrived.',
            ]);
        }

        $note = trim($note);

        if ($note === '') {
            throw ValidationException::withMessages([
                'note' => 'Explain how the customer was verified.',
            ]);
        }

        $driver = $fulfillment->driver;

        if ($driver === null) {
            throw ValidationException::withMessages([
                'status' => 'Assign a driver before handover.',
            ]);
        }

        $delivered = $this->finalize(
            $fulfillment,
            $driver,
            $staff,
            'Delivered without code: '.$method->label().'. '.$note,
        );

        $order = $delivered->order;

        if ($order?->customer_id !== null) {
            $this->notifications->send(
                [$order->customer_id],
                NotificationType::Order,
                'Order delivered',
                'Your order '.$order->order_number.' was marked delivered by our staff without a delivery code. Contact us if this is wrong.',
                ['order_number' => $order->order_number],
            );
        }

        return $delivered;
    }

    public function fail(
        OrderFulfillment $fulfillment,
        User $actor,
        DeliveryFailureReason $reason,
        ?string $note = null,
    ): OrderFulfillment {
        $this->assertHandoverActor($fulfillment, $actor);

        if ($fulfillment->status !== FulfillmentStatus::Arrived) {
            throw ValidationException::withMessages([
                'status' => 'Mark an order as not delivered only after arrival.',
            ]);
        }

        if (trim((string) $note) === '') {
            throw ValidationException::withMessages([
                'note' => 'Explain why the order could not be delivered.',
            ]);
        }

        DeliveryAttempt::query()->create([
            'fulfillment_id' => $fulfillment->id,
            'driver_id' => $fulfillment->driver_id,
            'reason' => $reason,
            'note' => $note,
            'attempted_at' => now(),
        ]);

        $comment = $reason->label();
        $trimmedNote = trim((string) $note);
        if ($trimmedNote !== '') {
            $comment .= ': '.$trimmedNote;
        }

        return $this->statuses->apply($fulfillment, FulfillmentStatus::DeliveryFailed, $actor, $comment);
    }

    public function retry(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        throw ValidationException::withMessages([
            'status' => 'Send the goods back to the branch before assigning a new driver.',
        ]);
    }

    public function reschedule(OrderFulfillment $fulfillment, User $actor): OrderFulfillment
    {
        throw ValidationException::withMessages([
            'status' => 'Send the goods back to the branch before assigning a new driver.',
        ]);
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

        return $this->statuses->apply($fulfillment, FulfillmentStatus::Returned, $actor, 'Goods received');
    }

    private function finalize(OrderFulfillment $fulfillment, User $collector, User $actor, string $comment): OrderFulfillment
    {
        return DB::transaction(function () use ($fulfillment, $collector, $actor, $comment): OrderFulfillment {
            $this->payments->collect($fulfillment->order, $collector);
            $fulfillment->forceFill(['paid_at' => now()])->save();
            $this->saleReserved($fulfillment, $actor);

            return $this->statuses->apply($fulfillment->refresh(), FulfillmentStatus::Delivered, $actor, $comment);
        });
    }

    private function saleReserved(OrderFulfillment $fulfillment, User $actor): void
    {
        $order = $fulfillment->order()->with('items')->first();

        if ($order === null) {
            return;
        }

        // Pack create already deducted branch stock via PackService::allocate.
        if ($order->isPackCheckout()) {
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

    private function assertHandoverActor(OrderFulfillment $fulfillment, User $actor): void
    {
        if ($fulfillment->driver_id === null) {
            throw ValidationException::withMessages([
                'status' => 'Assign a driver before handover.',
            ]);
        }

        if ($actor->isDriver() && $fulfillment->driver_id !== $actor->id) {
            abort(404);
        }

        if (! $actor->isDriver() && ! $actor->isPlatform() && ! $actor->isBranch()) {
            abort(403);
        }
    }
}
