<?php

use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform user lands on platform dashboard with shared auth props', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/platform/dashboard');

    $this->get('/platform/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/dashboard')
            ->where('auth.user.name', 'Ava Mitchell')
            ->where('auth.user.is_super_admin', true)
            ->where('auth.user.roles', fn ($roles) => collect($roles)->contains('super-admin'))
            ->where('portal', 'platform')
            ->where('branch', null)
        );
});

test('branch user lands on branch dashboard with branch context', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/branch/sydney-cbd/dashboard');

    $this->get('/branch/sydney-cbd/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/dashboard')
            ->where('auth.user.name', 'Alex Chen')
            ->where('auth.user.is_super_admin', true)
            ->where('auth.user.roles', fn ($roles) => collect($roles)->contains('super-admin'))
            ->where('portal', 'branch')
            ->where('branch.slug', BranchSeeder::SYDNEY_CBD)
            ->where('branch.name', 'PuffMeMate Sydney CBD')
        );
});

test('driver user lands on driver dashboard', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::DRIVER_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/driver/dashboard');

    $this->get('/driver/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/dashboard')
            ->where('auth.user.name', 'Daniel Foster')
            ->where('portal', 'driver')
        );
});

test('customer user lands on customer dashboard', function () {
    $response = $this->post(route('login.store'), [
        'phone' => UserSeeder::CUSTOMER_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ]);

    $response->assertRedirect('/dashboard');

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/dashboard')
            ->where('auth.user.name', 'Emma Walker')
            ->where('portal', 'customer')
        );
});

test('portal dashboards keep placeholder boards client-side', function (
    string $phone,
    string $url,
    string $component,
) {
    $this->post(route('login.store'), [
        'phone' => $phone,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ])->assertRedirect();

    $this->get($url)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->missing('kpis')
            ->missing('orders')
            ->missing('stats')
        );
})->with([
    'platform' => [UserSeeder::PLATFORM_SUPER_ADMIN_PHONE, '/platform/dashboard', 'platform/dashboard'],
    'branch' => [UserSeeder::BRANCH_1_SUPER_ADMIN_PHONE, '/branch/sydney-cbd/dashboard', 'branch/dashboard'],
    'driver' => [UserSeeder::DRIVER_PHONE, '/driver/dashboard', 'driver/dashboard'],
    'customer' => [UserSeeder::CUSTOMER_PHONE, '/dashboard', 'customer/dashboard'],
]);
