<?php

namespace App\Actions\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\NotificationType;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Notifications\NotificationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CollectLateOrderItemAction
{
    public function __construct(
        private InventoryReservationService $reservations,
        private NotificationService $notifications,
    ) {}

    public function handle(OrderItem $item, User $actor): Order
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
                'item' => 'Only late-delivery items can be collected this way.',
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

        if (! $this->allowsCollect($fulfillment->status)) {
            throw ValidationException::withMessages([
                'item' => 'Late items can no longer be collected at this stage.',
            ]);
        }

        $collectQty = (int) $item->quantity - (int) $item->local_reserved_quantity;

        if ($collectQty <= 0) {
            throw ValidationException::withMessages([
                'item' => 'There is nothing left to collect for this item.',
            ]);
        }

        $productName = $item->product_name_snapshot;
        $orderNumber = $order->order_number;

        $order = DB::transaction(function () use ($item, $actor, $order, $fulfillment, $collectQty): Order {
            $locked = $this->reservations->firstOrCreateLocked(
                $fulfillment->branch_id,
                (int) $item->product_id,
                $item->variant_id !== null ? (int) $item->variant_id : null,
            );

            $available = $this->reservations->available($locked);
            $stockInQty = max(0, $collectQty - $available);

            if ($stockInQty > 0) {
                $before = $locked->quantity;
                $locked->quantity = $before + $stockInQty;
                $locked->save();

                InventoryTransaction::query()->create([
                    'branch_id' => $locked->branch_id,
                    'product_id' => $locked->product_id,
                    'variant_id' => $locked->variant_id,
                    'type' => InventoryTransactionType::StockIn,
                    'reason' => null,
                    'reason_note' => 'Late collect for order '.$order->order_number,
                    'quantity' => $stockInQty,
                    'before_quantity' => $before,
                    'after_quantity' => $locked->quantity,
                    'unit_cost' => null,
                    'reference_type' => $order->getMorphClass(),
                    'reference_id' => $order->getKey(),
                    'created_by' => $actor->id,
                    'created_at' => now(),
                ]);
            }

            $this->reservations->reserve($locked, $collectQty, $order, $actor);

            $item->forceFill([
                'local_reserved_quantity' => (int) $item->quantity,
                'is_late_delivery' => false,
            ])->save();

            OrderStatusHistory::query()->create([
                'order_id' => $order->id,
                'fulfillment_id' => $fulfillment->id,
                'old_status' => $fulfillment->status->value,
                'new_status' => $fulfillment->status->value,
                'comment' => 'Collected late item: '.$item->product_name_snapshot.' ('.$collectQty.' units)',
                'changed_by' => $actor->id,
            ]);

            $stillLate = $order->items()
                ->whereNull('removed_at')
                ->where('is_late_delivery', true)
                ->exists();

            if (! $stillLate && $order->accepts_late_delivery) {
                $order->forceFill(['accepts_late_delivery' => false])->save();
            }

            return $order->refresh();
        });

        $customerId = $order->customer_id;

        if ($customerId !== null) {
            $this->notifications->send(
                [$customerId],
                NotificationType::Order,
                'Item ready for your order',
                '“'.$productName.'” is now in stock for order '.$orderNumber.'.',
                [
                    'order_number' => $orderNumber,
                    'product_name' => $productName,
                ],
                $actor,
            );
        }

        return $order;
    }

    private function allowsCollect(FulfillmentStatus $status): bool
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
}
