<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\OrderReturnStatus;
use App\Enums\OrderReturnType;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderReturn;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderReturnService
{
    public function __construct(
        private FulfillmentStatusService $statuses,
        private PaymentMarkingService $payments,
        private InventoryService $inventory,
    ) {}

    public function request(
        Order $order,
        User $requester,
        OrderReturnType $type,
        string $reason,
        ?string $note = null,
    ): OrderReturn {
        $order->loadMissing('fulfillment');
        $fulfillment = $order->fulfillment;

        if ($fulfillment === null || ! in_array($fulfillment->status, [FulfillmentStatus::Arrived, FulfillmentStatus::Delivered], true)) {
            throw ValidationException::withMessages([
                'reason' => 'A return can be requested after the driver has arrived.',
            ]);
        }

        if ($requester->isCustomer() && $order->customer_id !== $requester->id) {
            abort(403);
        }

        if ($requester->isDriver() && $fulfillment->driver_id !== $requester->id) {
            abort(403);
        }

        if (! $requester->isCustomer() && ! $requester->isDriver()) {
            abort(403);
        }

        $open = OrderReturn::query()
            ->where('order_id', $order->id)
            ->where('status', OrderReturnStatus::Requested)
            ->exists();

        if ($open) {
            throw ValidationException::withMessages([
                'reason' => 'This order already has an open return request.',
            ]);
        }

        return OrderReturn::query()->create([
            'order_id' => $order->id,
            'requester_id' => $requester->id,
            'type' => $type,
            'reason' => $reason,
            'note' => $note,
            'status' => OrderReturnStatus::Requested,
        ]);
    }

    public function approve(OrderReturn $return, User $actor): OrderReturn
    {
        if ($return->status !== OrderReturnStatus::Requested) {
            throw ValidationException::withMessages([
                'status' => 'This return request is already resolved.',
            ]);
        }

        $order = $return->order()->with(['fulfillment', 'items'])->firstOrFail();
        $fulfillment = $order->fulfillment;

        if ($fulfillment === null) {
            throw ValidationException::withMessages([
                'status' => 'This order has no fulfillment.',
            ]);
        }

        DB::transaction(function () use ($return, $order, $fulfillment, $actor): void {
            if (in_array($order->payment_status, [PaymentStatus::Collected, PaymentStatus::Verified], true)) {
                $this->payments->refund($order, $actor);
                $this->restock($order, $actor);
            } else {
                $this->statuses->cancel($fulfillment, $actor, 'Defect return approved', true);
            }

            $return->forceFill([
                'status' => OrderReturnStatus::Approved,
                'resolved_by' => $actor->id,
                'resolved_at' => now(),
            ])->save();
        });

        return $return->refresh();
    }

    public function reject(OrderReturn $return, User $actor): OrderReturn
    {
        if ($return->status !== OrderReturnStatus::Requested) {
            throw ValidationException::withMessages([
                'status' => 'This return request is already resolved.',
            ]);
        }

        $return->forceFill([
            'status' => OrderReturnStatus::Rejected,
            'resolved_by' => $actor->id,
            'resolved_at' => now(),
        ])->save();

        return $return->refresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function payload(Order $order): ?array
    {
        $return = $order->returns()->latest('id')->first();

        if ($return === null) {
            return null;
        }

        return [
            'id' => $return->id,
            'type' => $return->type->value,
            'typeLabel' => $return->type->label(),
            'reason' => $return->reason,
            'note' => $return->note,
            'status' => $return->status->value,
            'statusLabel' => $return->status->label(),
        ];
    }

    private function restock(Order $order, User $actor): void
    {
        $fulfillment = $order->fulfillment;

        if ($fulfillment === null) {
            return;
        }

        foreach ($order->items as $item) {
            $query = Inventory::query()
                ->where('branch_id', $fulfillment->branch_id)
                ->where('product_id', $item->product_id);

            if ($item->variant_id === null) {
                $query->whereNull('variant_id');
            } else {
                $query->where('variant_id', $item->variant_id);
            }

            $row = $query->first();

            if ($row === null || $item->quantity < 1) {
                continue;
            }

            $this->inventory->adjust(
                $row,
                InventoryTransactionType::StockIn,
                $item->quantity,
                $actor,
                $order::class,
                $order->id,
            );
        }
    }
}
