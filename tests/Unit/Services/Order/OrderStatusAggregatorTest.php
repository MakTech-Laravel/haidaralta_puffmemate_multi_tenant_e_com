<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\OrderStatusAggregator;

test('the single fulfillment copies its status onto the master order', function () {
    $order = Order::factory()->status(OrderStatus::Pending)->create();
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Delivered]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Delivered);
});

test('a cancelled fulfillment copies cancelled onto the master order', function () {
    $order = Order::factory()->status(OrderStatus::Pending)->create();
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Cancelled]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Cancelled);
});

test('processing on the fulfillment rolls a pending order up to processing', function () {
    $order = Order::factory()->status(OrderStatus::Pending)->create();
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Processing]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Processing);
});
