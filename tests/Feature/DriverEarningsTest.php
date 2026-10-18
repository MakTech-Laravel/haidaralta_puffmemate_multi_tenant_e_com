<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver earnings', function () {
    $this->get(route('driver.earnings.index'))
        ->assertRedirect(route('login'));
});

test('drivers can view earnings for completed drops today by default', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $order = Order::factory()->create([
        'shipping_address_snapshot' => [
            'name' => 'James Okonkwo',
            'city' => 'Sydney',
            'address' => '1 Test Street',
        ],
        'order_number' => 'PMM-2026-0100',
        'total_amount' => 40,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'amount' => 40,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now(),
    ]);
    Payment::factory()->create([
        'order_id' => Order::factory()->create()->id,
        'amount' => 20,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now()->subDays(2),
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
        'updated_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/earnings/index')
            ->where('filters.period', 'today')
            ->where('earnings.period', 'today')
            ->where('earnings.totalLabel', '$40.00')
            ->where('earnings.drops', 1)
            ->where('earnings.deliveries', 1)
            ->where('earnings.days.0.entries.0.customerName', 'James Okonkwo')
            ->where('earnings.days.0.entries.0.id', 'PMM-2026-0100'));
});

test('seven day earnings includes recent collections and excludes older ones', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $recent = Order::factory()->create(['order_number' => 'PMM-2026-0101']);
    Payment::factory()->create([
        'order_id' => $recent->id,
        'amount' => 30,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now()->subDays(3),
    ]);
    Payment::factory()->create([
        'order_id' => Order::factory()->create()->id,
        'amount' => 99,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now()->subDays(10),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index', ['period' => '7d']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('filters.period', '7d')
            ->where('earnings.period', '7d')
            ->where('earnings.totalLabel', '$30.00')
            ->where('earnings.drops', 1)
            ->where('earnings.days.0.entries.0.id', 'PMM-2026-0101'));
});

test('earnings performance compares today against yesterday', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    Payment::factory()->create([
        'order_id' => Order::factory()->create()->id,
        'amount' => 40,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now(),
    ]);
    Payment::factory()->create([
        'order_id' => Order::factory()->create()->id,
        'amount' => 20,
        'gateway' => 'cod',
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
        'collected_at' => now()->subDay(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('earnings.totalLabel', '$40.00')
            ->where('earnings.performance.previousTotalLabel', '$20.00')
            ->where('earnings.performance.changePercent', 100)
            ->where('earnings.performance.changeAmountLabel', '+$20.00')
            ->where('earnings.performance.direction', 'up')
            ->where('earnings.performance.comparisonLabel', 'vs yesterday'));
});

test('earnings deliveries performance compares today against yesterday', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    $todayOrder = Order::factory()->create();
    $yesterdayOrder = Order::factory()->create();

    OrderFulfillment::factory()->forOrder($todayOrder)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
        'updated_at' => now(),
    ]);
    OrderFulfillment::factory()->forOrder($yesterdayOrder)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
        'updated_at' => now()->subDay(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('earnings.deliveries', 1)
            ->where('earnings.deliveriesPerformance.previousTotalLabel', '1')
            ->where('earnings.deliveriesPerformance.changePercent', 0)
            ->where('earnings.deliveriesPerformance.direction', 'flat')
            ->where('earnings.deliveriesPerformance.comparisonLabel', 'vs yesterday'));
});
