<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from customer orders', function () {
    $this->get(route('customer.orders.index'))
        ->assertRedirect(route('login'));
});

test('customers can view the demo orders page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/index')
            ->has('orders')
            ->where('orders.0.orderNumber', '#ORD-CP2026-001')
            ->has('orders', 13));
});

test('guests are redirected from customer order tracking', function () {
    $this->get(route('customer.orders.show', 'ord-1'))
        ->assertRedirect(route('login'));
});

test('customers can view a demo order tracking page', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.orders.show', 'ord-1'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->where('order.id', 'ord-1')
            ->where('order.orderNumber', '#ORD-CP2026-001')
            ->has('order.products')
            ->has('order.shippingAddress')
            ->has('order.driver')
            ->has('order.timeline'));
});

test('customers receive not found for unknown demo orders', function () {
    $user = User::factory()->create();
    $this->actingAs($user, 'customer');

    $this->get(route('customer.orders.show', 'ord-missing'))
        ->assertNotFound();
});
