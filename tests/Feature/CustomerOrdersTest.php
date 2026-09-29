<?php

use App\Enums\FulfillmentStatus;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer orders', function () {
    $this->get(route('customer.orders.index'))
        ->assertRedirect(route('login'));
});

test('customers can view an empty orders page', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/index')
            ->has('orders', 0)
            ->missing('orders.0.branchName'));
});

test('guests are redirected from customer order tracking', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $user->id]);

    $this->get(route('customer.orders.show', $order))
        ->assertRedirect(route('login'));
});

test('customers receive not found for unknown orders', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'customer')
        ->get(route('customer.orders.show', 'ord-missing'))
        ->assertNotFound();
});

test('a customer cannot open another customers order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $owner->id]);

    $this->actingAs($other, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertNotFound();
});

test('a customer cannot cancel another customers order', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    $order = Order::factory()->create(['customer_id' => $owner->id]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Pending,
    ]);

    $this->actingAs($other, 'customer')
        ->post(route('customer.orders.cancel', $order))
        ->assertNotFound();

    expect($order->refresh()->fulfillment->status)->toBe(FulfillmentStatus::Pending);
});

test('the customer order detail payload has no internal fields', function () {
    $user = User::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $user->id,
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410111222',
            'address' => '1 Test Street',
            'city' => 'Sydney',
        ],
    ]);
    OrderFulfillment::factory()->forOrder($order)->create();

    $this->actingAs($user, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->missing('order.branch')
            ->missing('order.branchName')
            ->missing('order.branchId')
            ->missing('order.fulfillmentId')
            ->missing('order.driverId')
            ->missing('order.driver_id')
            ->missing('order.transfers')
            ->missing('order.sourceBranch')
            ->missing('order.deliveryAttempts')
            ->missing('order.failureReason'));
});
