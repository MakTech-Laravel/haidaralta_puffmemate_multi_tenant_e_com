<?php

use App\Enums\UserType;
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

    $this->actingAs($driver, 'driver')
        ->get(route('driver.earnings.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/earnings/index')
            ->where('earnings.todayLabel', '$194.40')
            ->where('earnings.weekLabel', '$612.10')
            ->where('earnings.tipsLabel', '$18.00')
            ->where('earnings.perStopLabel', '$21.60')
            ->where('earnings.payoutStatus', 'Clears tonight')
            ->where('earnings.dropsToday', 1)
            ->where('earnings.dropsWeek', 7)
            ->where('earnings.days.0.label', 'Today')
            ->where('earnings.days.0.entries.0.customerName', 'James Okonkwo')
            ->where('earnings.days.1.label', 'Yesterday')
            ->where('earnings.days.1.entries.0.customerName', 'Maya Patel'));
});
