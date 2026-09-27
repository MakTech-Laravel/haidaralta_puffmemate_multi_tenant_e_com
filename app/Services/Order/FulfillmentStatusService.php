<?php

namespace App\Services\Order;

use App\Enums\FulfillmentStatus;
use App\Models\OrderFulfillment;
use App\Models\OrderStatusHistory;
use App\Models\User;

class FulfillmentStatusService
{
    public function __construct(private OrderStatusAggregator $aggregator) {}

    /**
     * Transition a fulfillment's status, log the change, and roll the new
     * state up into the master order's aggregated status.
     */
    public function transition(
        OrderFulfillment $fulfillment,
        FulfillmentStatus $status,
        ?User $actor = null,
        ?string $comment = null,
    ): OrderFulfillment {
        if ($fulfillment->status === $status) {
            return $fulfillment;
        }

        $old = $fulfillment->status;
        $fulfillment->forceFill(['status' => $status])->save();

        OrderStatusHistory::query()->create([
            'order_id' => $fulfillment->order_id,
            'fulfillment_id' => $fulfillment->id,
            'old_status' => $old->value,
            'new_status' => $status->value,
            'comment' => $comment ?? 'Status updated',
            'changed_by' => $actor?->id,
        ]);

        $this->aggregator->recompute($fulfillment->order()->first());

        return $fulfillment->refresh();
    }
}
