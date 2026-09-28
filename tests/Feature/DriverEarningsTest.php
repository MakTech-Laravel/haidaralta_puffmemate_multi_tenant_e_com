<?php

use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests are redirected from driver earnings', function () {
    $this->get(route('driver.earnings.index'))
        ->assertRedirect(route('login'));
});

test('drivers can view earnings for completed drops', function () {
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

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/earnings/index')
            ->where('earnings.todayLabel', '$40.00')
            ->where('earnings.dropsToday', 1)
            ->where('earnings.days.0.entries.0.customerName', 'James Okonkwo'));
});
