<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Services\Conversation\OrderConversationCloser;
use Illuminate\Support\Collection;

/**
 * Rolls per-branch fulfillment statuses up into the master order status.
 *
 * `PartiallyDelivered` is always derived here, never set directly by a form —
 * see the recompute() rules below.
 */
class OrderStatusAggregator
{
    public function __construct(private OrderConversationCloser $closer) {}

    public function recompute(Order $order): Order
    {
        $order->loadMissing('fulfillments');

        $statuses = $order->fulfillments->pluck('status');

        if ($statuses->isEmpty()) {
            return $order;
        }

        $target = $this->resolve($statuses, $order->status);

        if ($target === null || $target === $order->status) {
            return $order;
        }

        OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'fulfillment_id' => null,
            'old_status' => $order->status->value,
            'new_status' => $target->value,
            'comment' => 'Recalculated from fulfillment statuses.',
        ]);

        return $this->closer->transition($order, $target);
    }

    /**
     * @param  Collection<int, FulfillmentStatus>  $statuses
     */
    private function resolve(Collection $statuses, OrderStatus $current): ?OrderStatus
    {
        if ($current === OrderStatus::Cancelled) {
            return null;
        }

        if ($statuses->every(fn (FulfillmentStatus $status): bool => $status === FulfillmentStatus::Cancelled)) {
            return OrderStatus::Cancelled;
        }

        $allTerminal = $statuses->every(
            fn (FulfillmentStatus $status): bool => in_array($status, [FulfillmentStatus::Delivered, FulfillmentStatus::Cancelled], true),
        );

        if ($allTerminal && $statuses->contains(FulfillmentStatus::Delivered)) {
            return OrderStatus::Delivered;
        }

        if ($statuses->contains(FulfillmentStatus::Delivered)) {
            return OrderStatus::PartiallyDelivered;
        }

        $anyInProgress = $statuses->contains(
            fn (FulfillmentStatus $status): bool => in_array($status, [FulfillmentStatus::Processing, FulfillmentStatus::Ready], true),
        );

        if ($anyInProgress && in_array($current, [OrderStatus::Pending, OrderStatus::Confirmed, OrderStatus::Processing], true)) {
            return OrderStatus::Processing;
        }

        return null;
    }
}
