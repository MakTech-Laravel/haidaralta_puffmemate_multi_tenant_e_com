<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver orders', function () {
    $this->get(route('driver.orders.index'))
        ->assertRedirect(route('login'));
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
            ->has('orders.0.customerName')
            ->has('orders.0.areaId')
            ->where('orders.0.areaLabel', 'Surry Hills')
            ->has('orders.0.phone')
            ->has('orders.0.productPreview')
            ->where('orders.0.paymentMethod', 'cod')
            ->has('orders', 10));
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
            ->where('order.areaLabel', 'Surry Hills')
            ->has('order.products')
            ->has('order.demoOtpHint')
            ->where('order.paymentMethod', 'cod')
            ->has('order.shippingAddress'));
});

test('guests are redirected from driver order chat', function () {
    $this->get(route('driver.orders.chat', 'drv-ord-1001'))
        ->assertRedirect(route('login'));
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
            ->where('order.customerName', 'Elena Vargas')
            ->where('order.areaLabel', 'Surry Hills')
            ->where('viewerId', $driver->id)
            ->where('canSend', true)
            ->has('messages')
            ->has('messages.0.id')
            ->has('messages.0.type')
            ->has('messages.0.body'));
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

test('delivered order chat is read-only', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.chat', 'drv-ord-1004'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/chat')
            ->where('order.status', 'delivered')
            ->where('canSend', false)
            ->has('messages'));
});

test('drivers can view old orders history', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.history.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/history')
            ->has('orders', 6)
            ->where('orders.0.customerName', 'Maya Patel')
            ->where('orders.0.areaLabel', 'Paddington')
            ->where('orders.0.status', 'delivered')
            ->where('orders.0.completedLabel', 'Yesterday'));
});

test('guests are redirected from old orders', function () {
    $this->get(route('driver.history.index'))
        ->assertRedirect(route('login'));
});

test('drivers can view a past order detail', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', 'drv-ord-0901'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.id', 'drv-ord-0901')
            ->where('order.orderNumber', 'POM-DRV-0901')
            ->where('order.areaLabel', 'Paddington')
            ->where('order.status', 'delivered'));
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
            ->has('summary.areaCount')
            ->has('summary.activeOrders')
            ->has('summary.nextStop')
            ->where('summary.currentArea.label', 'Surry Hills')
            ->where('summary.currentArea.remainingCount', 6)
            ->where('summary.laterAreas.0.label', 'Darlinghurst')
            ->where('summary.nextStop.customerName', 'Elena Vargas')
            ->where('summary.nextStop.areaLabel', 'Surry Hills')
            ->has('hub.shift.label')
            ->has('hub.earnings.todayLabel')
            ->has('hub.performance.rating')
            ->has('hub.vehicle.name')
            ->has('hub.alerts'));
});
