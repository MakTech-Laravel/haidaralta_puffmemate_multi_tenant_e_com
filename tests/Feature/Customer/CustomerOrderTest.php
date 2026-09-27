<?php

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Product;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

function customerOrderWithItem(User $customer, array $overrides = []): Order
{
    $order = Order::factory()->for($customer, 'customer')->create(array_merge([
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '0410123456',
            'address' => '1 Test Street',
            'city' => 'Sydney',
            'postal_code' => '2000',
        ],
        'subtotal' => 60,
        'delivery_fee' => 10,
        'total_amount' => 70,
        'payment_status' => PaymentStatus::Unpaid,
    ], $overrides));

    $product = Product::factory()->create(['name' => 'Test Product']);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => null,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name, 'image' => null],
        'quantity' => 2,
        'unit_price' => 30,
        'subtotal' => 60,
    ]);

    OrderStatusHistory::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => null,
        'old_status' => null,
        'new_status' => OrderStatus::Pending->value,
        'comment' => 'Order placed',
    ]);

    return $order->refresh();
}

test('customer order list only shows their own orders', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();

    customerOrderWithItem($customer);
    customerOrderWithItem($other);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/index')
            ->has('orders', 1)
            ->missing('orders.0.image')
            ->missing('orders.0.id'));
});

test('customer order detail never leaks branch or fulfillment data', function () {
    $customer = User::factory()->create();
    $order = customerOrderWithItem($customer);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->where('order.orderNumber', $order->order_number)
            ->missing('order.id')
            ->missing('order.branch')
            ->missing('order.fulfillments')
            ->missing('order.branchName'));
});

test('customer order detail uses the order number not the database id', function () {
    $customer = User::factory()->create();
    $order = customerOrderWithItem($customer, [
        'order_number' => 'PMM-2026-2451',
    ]);

    $this->actingAs($customer, 'customer')
        ->get('/orders/'.$order->id)
        ->assertNotFound();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->where('order.orderNumber', 'PMM-2026-2451')
            ->missing('order.id'));

    expect(parse_url(route('customer.orders.show', $order), PHP_URL_PATH))
        ->toBe('/orders/PMM-2026-2451');
});

test('a customer cannot view another customers order', function () {
    $customer = User::factory()->create();
    $other = User::factory()->create();
    $order = customerOrderWithItem($other);

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertNotFound();
});
