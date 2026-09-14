<?php

use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform user is redirected to platform dashboard after login', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/platform/dashboard');
    $this->assertAuthenticated('platform');
});

test('login accepts html checkbox remember value', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
        'remember' => 'on',
    ]);

    $response->assertRedirect('/platform/dashboard');
    $this->assertAuthenticated('platform');
});

test('branch user is redirected to their branch dashboard after login', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/branch/sydney-cbd/dashboard');
    $this->assertAuthenticated('branch');
});

test('driver user is redirected to driver dashboard after login', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::DRIVER_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/driver/dashboard');
    $this->assertAuthenticated('driver');
});

test('customer user is redirected to customer dashboard after login', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::CUSTOMER_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/dashboard');
    $this->assertAuthenticated('customer');
});

test('wrong password returns generic auth failed message', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrors('phone', __('auth.failed'));
    $this->assertGuest('platform');
});

test('unknown phone returns the same generic auth failed message', function () {
    $response = $this->post(route('login.store'), [
        'phone' => '0412987654',
        'password' => 'password',
    ]);

    $response->assertSessionHasErrors('phone', __('auth.failed'));
    $this->assertGuest('platform');
    $this->assertGuest('branch');
    $this->assertGuest('driver');
    $this->assertGuest('customer');
});

test('inactive users cannot log in', function () {
    User::query()->where('phone', UserSeeder::PLATFORM_SUPER_ADMIN_PHONE)->update(['is_active' => false]);

    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertSessionHasErrors('phone', __('auth.failed'));
    $this->assertGuest('platform');
});

test('authenticated portal users visiting login are redirected to their dashboard', function () {
    $user = User::query()->where('phone', UserSeeder::PLATFORM_SUPER_ADMIN_PHONE)->firstOrFail();

    $response = $this->actingAs($user, 'platform')->get(route('login'));

    $response->assertRedirect('/platform/dashboard');
});

test('sixth failed login attempt within a minute is rate limited', function () {
    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->post(route('login.store'), [
            'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
            'password' => 'wrong-password',
        ]);
    }

    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
