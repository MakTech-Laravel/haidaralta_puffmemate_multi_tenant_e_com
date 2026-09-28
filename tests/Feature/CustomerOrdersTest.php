<?php

use App\Models\Order;
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
