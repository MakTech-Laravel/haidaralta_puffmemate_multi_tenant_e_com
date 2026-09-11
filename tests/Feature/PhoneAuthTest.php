<?php

use App\Models\User;
use App\Support\Phone;
use Database\Seeders\UserSeeder;

test('local au and e164 phones normalize to the same value', function () {
    expect(Phone::toE164('0412345678'))->toBe('+61412345678')
        ->and(Phone::toE164('+61412345678'))->toBe('+61412345678');
});

test('users can authenticate with a local au phone number', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
    ]);

    $response = $this->post(route('login.store'), [
        'phone' => '0412345678',
        'password' => 'password',
    ]);

    $this->assertAuthenticated('customer');
    $response->assertRedirect('/dashboard');
});

test('users can authenticate with an e164 phone number', function () {
    $user = User::factory()->create([
        'phone' => '+61412345678',
    ]);

    $this->post(route('login.store'), [
        'phone' => '+61412345678',
        'password' => 'password',
    ]);

    $this->assertAuthenticated('customer');
});

test('invalid phones are rejected on login', function () {
    $this->post(route('login.store'), [
        'phone' => 'not-a-phone',
        'password' => 'password',
    ])->assertSessionHasErrors([
        'phone' => __('validation.phone', ['attribute' => 'phone number']),
    ]);

    $this->assertGuest('customer');
});

test('non australian mobiles show a friendly phone validation message', function () {
    $this->post(route('register.store'), [
        'name' => 'User User',
        'phone' => '+880123456789',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertSessionHasErrors([
        'phone' => __('validation.phone', ['attribute' => 'phone number']),
    ]);
});

test('seeded platform super admin can login with phone and shared password', function () {
    $this->seed();

    $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $this->assertAuthenticated('platform');
});
