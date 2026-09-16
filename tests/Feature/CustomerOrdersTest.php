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
