<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver place order', function () {
    $this->get(route('driver.place-order.index'))
        ->assertRedirect(route('login'));
});

test('drivers can open the design-only place order page', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.place-order.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/place-order/index')
            ->has('customers')
            ->has('products')
            ->has('paymentMethods')
            ->where('customers.0.name', 'James Okonkwo')
            ->where('products.2.stock', 0)
            ->where('paymentMethods.0.id', 'cod'));
});
