<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver orders', function () {
    $this->get(route('driver.orders.index'))
        ->assertRedirect();
});

test('drivers can view assigned orders index', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/index')
            ->has('orders')
            ->has('orders.0.id')
            ->has('orders.0.orderNumber')
            ->has('orders.0.status')
            ->has('orders.0.customerName'));
});

test('drivers can view an assigned order detail', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', 'drv-ord-1001'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.id', 'drv-ord-1001')
            ->where('order.orderNumber', 'POM-DRV-1001')
            ->has('order.products')
            ->has('order.demoOtpHint')
            ->has('order.shippingAddress'));
});

test('guests are redirected from driver order chat', function () {
    $this->get(route('driver.orders.chat', 'drv-ord-1001'))
        ->assertRedirect();
});

test('drivers can open chat for an assigned order', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', 'drv-ord-1001'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/chat')
            ->where('order.id', 'drv-ord-1001')
            ->where('order.orderNumber', 'POM-DRV-1001')
            ->where('order.customerName', 'Elena Vargas'));
});

test('unknown driver order chats return 404', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', 'missing-order'))
        ->assertNotFound();
});

test('unknown driver orders return 404', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', 'missing-order'))
        ->assertNotFound();
});

test('deliver rejects a short otp', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', 'drv-ord-1001'))
        ->post(route('driver.orders.deliver', 'drv-ord-1001'), [
            'otp' => '12',
        ])
        ->assertSessionHasErrors('otp')
        ->assertRedirect(route('driver.orders.show', 'drv-ord-1001'));
});

test('deliver accepts a valid otp and flashes success', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', 'drv-ord-1001'))
        ->post(route('driver.orders.deliver', 'drv-ord-1001'), [
            'otp' => '482913',
        ])
        ->assertSessionHasNoErrors()
        ->assertSessionHas('success')
        ->assertRedirect(route('driver.orders.index'));
});

test('driver dashboard receives assigned order summary', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/dashboard')
            ->has('summary.activeCount')
            ->has('summary.deliveredCount')
            ->has('summary.assignedCount')
            ->has('summary.activeOrders')
            ->has('summary.nextStop'));
});
