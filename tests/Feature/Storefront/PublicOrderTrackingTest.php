<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Support\Order\OrderTrackingLink;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * A guest-style order with one item, optionally closed (delivered/cancelled) some days ago.
 */
function trackingOrder(OrderStatus $status = OrderStatus::Placed, ?int $closedDaysAgo = null): Order
{
    $order = Order::factory()->create([
        'status' => $status,
        'payment_status' => PaymentStatus::PendingCollection,
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410123456',
            'address' => '1 Test Street',
            'city' => 'Sydney',
            'postal_code' => '2000',
        ],
        'subtotal' => 80,
        'delivery_fee' => 10,
        'total_amount' => 90,
        'delivery_date' => now()->toDateString(),
        'time_slot' => 'Morning Delivery',
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create();
    $product = Product::factory()->create(['name' => 'Tracked Product']);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 2,
        'unit_price' => 40,
        'subtotal' => 80,
    ]);

    if ($closedDaysAgo !== null) {
        $history = OrderStatusHistory::query()->create([
            'order_id' => $order->id,
            'fulfillment_id' => $fulfillment->id,
            'old_status' => OrderStatus::Placed->value,
            'new_status' => $status->value,
            'comment' => 'Closed',
        ]);
        $history->forceFill(['created_at' => now()->subDays($closedDaysAgo)])->save();
    }

    return $order->refresh();
}

test('a signed link opens the read-only order page without logging in', function () {
    $order = trackingOrder();

    $this->get(OrderTrackingLink::url($order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('storefront/order-tracking')
            ->where('order.orderNumber', $order->order_number)
            ->has('order.products', 1)
            ->where('order.products.0.name', 'Tracked Product')
            ->where('order.totalLabel', '$90.00')
            ->where('order.shippingAddress.name', 'Ada Lovelace')
            ->where('order.shippingAddress.mobile', '*********456')
            ->has('order.timeline')
            ->missing('order.deliveryCode')
            ->missing('order.driver')
            ->missing('order.canCancel')
            ->missing('order.canChat')
            ->missing('order.source'));
});

test('the order page cannot be opened without a valid signature', function () {
    $order = trackingOrder();
    $other = trackingOrder();
    $signed = OrderTrackingLink::url($order);

    $this->get('/order-status/'.$order->order_number)->assertForbidden();
    $this->get($signed.'x')->assertForbidden();
    $this->get(str_replace($order->order_number, $other->order_number, $signed))->assertForbidden();
});

test('an order in progress keeps working however old it is', function () {
    $order = trackingOrder(OrderStatus::Processing);
    $order->forceFill(['created_at' => now()->subDays(90)])->save();

    $this->get(OrderTrackingLink::url($order->refresh()))->assertOk();
});

test('a completed order stays viewable for 30 days then expires', function (OrderStatus $status, int $closedDaysAgo, int $expected) {
    $order = trackingOrder($status, $closedDaysAgo);

    $this->get(OrderTrackingLink::url($order))->assertStatus($expected);
})->with([
    'delivered 10 days ago' => [OrderStatus::Delivered, 10, 200],
    'delivered 29 days ago' => [OrderStatus::Delivered, 29, 200],
    'delivered 31 days ago' => [OrderStatus::Delivered, 31, 410],
    'cancelled 10 days ago' => [OrderStatus::Cancelled, 10, 200],
    'cancelled 31 days ago' => [OrderStatus::Cancelled, 31, 410],
]);
