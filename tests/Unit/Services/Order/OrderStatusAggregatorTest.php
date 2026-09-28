<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\OrderStatusAggregator;

function orderWithFulfillments(OrderStatus $orderStatus, array $fulfillmentStatuses): Order
{
    $order = Order::factory()->status($orderStatus)->create();

    foreach ($fulfillmentStatuses as $status) {
        OrderFulfillment::factory()->forOrder($order)->create(['status' => $status]);
    }

    return $order->refresh();
}

test('all fulfillments delivered rolls the master order up to delivered', function () {
    $order = orderWithFulfillments(OrderStatus::Processing, [
        FulfillmentStatus::Delivered,
        FulfillmentStatus::Delivered,
    ]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Delivered);
});

test('all fulfillments cancelled rolls the master order up to cancelled', function () {
    $order = orderWithFulfillments(OrderStatus::Pending, [
        FulfillmentStatus::Cancelled,
        FulfillmentStatus::Cancelled,
    ]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Cancelled);
});

test('a mix of delivered and still-active fulfillments rolls up to partially delivered', function () {
    $order = orderWithFulfillments(OrderStatus::Processing, [
        FulfillmentStatus::Delivered,
        FulfillmentStatus::Processing,
    ]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::PartiallyDelivered);
});

test('any fulfillment moving into processing rolls a pending order up to processing', function () {
    $order = orderWithFulfillments(OrderStatus::Pending, [
        FulfillmentStatus::Processing,
        FulfillmentStatus::Pending,
    ]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Processing);
});

test('a cancelled master order never gets overwritten by fulfillment recompute', function () {
    $order = orderWithFulfillments(OrderStatus::Cancelled, [
        FulfillmentStatus::Delivered,
    ]);

    $order = app(OrderStatusAggregator::class)->recompute($order);

    expect($order->status)->toBe(OrderStatus::Cancelled);
});
