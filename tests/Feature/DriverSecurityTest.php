<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open driver security', function () {
    $this->get(route('driver.security.index'))
        ->assertRedirect(route('login'));
});

test('drivers see a static security page for their sign-in status', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'phone' => '+61400000111',
        'phone_verified_at' => now(),
    ]);

    $driver->forceFill([
        'two_factor_secret' => 'secret',
        'two_factor_confirmed_at' => now(),
    ])->save();

    $driver->passkeys()->create([
        'name' => 'Phone',
        'credential_id' => 'driver-security-credential',
        'credential' => ['id' => 'driver-security-credential'],
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.security.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/security/index')
            ->where('phone', '+61400000111')
            ->where('phoneVerified', true)
            ->where('twoFactorEnabled', true)
            ->where('passkeyCount', 1)
            ->missing('password'));
});
