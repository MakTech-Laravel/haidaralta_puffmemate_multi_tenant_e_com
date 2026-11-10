<?php

namespace App\Actions\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\PaymentStatus;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Notifications\NotificationService;
use App\Services\Order\FulfillmentStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RemoveLateOrderItemAction
{
    public function __construct(
        private InventoryReservationService $reservations,
        private FulfillmentStatusService $statuses,
        private NotificationService $notifications,
    ) {}

    public function handle(OrderItem $item, User $actor, ?string $note = null): Order
    {
        $item->loadMissing(['order.fulfillment', 'order.customer', 'order.items']);

        $order = $item->order;

        if ($order === null) {
            throw ValidationException::withMessages([
                'item' => 'Order item not found.',
            ]);
        }

        if (! $item->is_late_delivery) {
            throw ValidationException::withMessages([
                'item' => 'Only late-delivery items can be removed this way.',
            ]);
        }

        if ($item->removed_at !== null) {
            throw ValidationException::withMessages([
                'item' => 'This item has already been removed.',
            ]);
        }

        $fulfillment = $order->fulfillment;

        if ($fulfillment === null) {
            throw ValidationException::withMessages([
                'item' => 'Order fulfillment is missing.',
            ]);
        }

        if (! $this->allowsRemoval($fulfillment->status)) {
            throw ValidationException::withMessages([
                'item' => 'Late items can no longer be removed at this stage.',
            ]);
        }

        $productName = $item->product_name_snapshot;
        $orderNumber = $order->order_number;

        $order = DB::transaction(function () use ($item, $actor, $note, $order, $fulfillment): Order {
            $this->releaseLocalReservation($item, $fulfillment->branch_id, $order, $actor);

            $item->forceFill([
                'removed_at' => now(),
                'removed_by' => $actor->id,
                'removal_note' => $note,
                'local_reserved_quantity' => 0,
            ])->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'fulfillment_id' => $fulfillment->id,
                'old_status' => $fulfillment->status->value,
                'new_status' => $fulfillment->status->value,
                'comment' => 'Removed late item: '.$item->product_name_snapshot,
                'changed_by' => $actor->id,
            ]);

            $activeItems = $order->items()
                ->whereNull('removed_at')
                ->get();

            if ($activeItems->isEmpty()) {
                $this->statuses->cancel(
                    $fulfillment->refresh(),
                    $actor,
                    'Order cancelled after removing the last late-delivery item.',
                );

                return $order->refresh();
            }

            $subtotal = round((float) $activeItems->sum('subtotal'), 2);
            $discount = min((float) $order->discount, $subtotal);
            $deliveryFee = (float) $order->delivery_fee;
            $total = round(max($subtotal - $discount + $deliveryFee, 0), 2);

            $order->forceFill([
                'subtotal' => $subtotal,
                'discount' => $discount,
                'total_amount' => $total,
            ])->save();

            $payment = $order->payment;

            if ($payment !== null && $payment->status === PaymentStatus::PendingCollection) {
                $payment->forceFill(['amount' => $total])->save();
            }

            return $order->refresh();
        });

        $customerId = $order->customer_id;

        if ($customerId !== null) {
            $cancelled = $order->fulfillment?->status === FulfillmentStatus::Cancelled;

            $this->notifications->send(
                [$customerId],
                NotificationType::Order,
                $cancelled ? 'Order cancelled' : 'Item removed from order',
                $cancelled
                    ? 'Your order '.$orderNumber.' was cancelled after “'.$productName.'” was removed.'
                    : '“'.$productName.'” was removed from order '.$orderNumber.'.',
                [
                    'order_number' => $orderNumber,
                    'product_name' => $productName,
                ],
                $actor,
            );
        }

        return $order;
    }

    private function allowsRemoval(FulfillmentStatus $status): bool
    {
        return match ($status) {
            FulfillmentStatus::Pending,
            FulfillmentStatus::Hold,
            FulfillmentStatus::Placed,
            FulfillmentStatus::Processing,
            FulfillmentStatus::Ready,
            FulfillmentStatus::Assigned => true,
            default => false,
        };
    }

    private function releaseLocalReservation(OrderItem $item, int $branchId, Order $order, User $actor): void
    {
        $qty = (int) $item->local_reserved_quantity;

        if ($qty <= 0) {
            return;
        }

        $query = Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $item->product_id);

        if ($item->variant_id === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $item->variant_id);
        }

        $inventory = $query->first();

        if ($inventory === null) {
            return;
        }

        $locked = $this->reservations->lockOrdered([$inventory->id]);
        $row = $locked[$inventory->id] ?? null;

        if ($row === null || $row->reserved_quantity < $qty) {
            return;
        }

        $this->reservations->release($row, $qty, $order, $actor);
    }
}
