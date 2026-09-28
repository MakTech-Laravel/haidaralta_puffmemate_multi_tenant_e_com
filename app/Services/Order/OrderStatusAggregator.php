<?php

namespace App\Services\Order;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;

/**
 * One fulfillment copies its status onto the master order.
 */
class OrderStatusAggregator
{
    public function recompute(Order $order): Order
    {
        $fulfillment = $order->fulfillment()->first() ?? $order->primaryFulfillment()->first();

        if ($fulfillment instanceof OrderFulfillment) {
            $order->forceFill(['status' => OrderStatus::from($fulfillment->status->value)])->save();
        }

        return $order->refresh();
    }
}
