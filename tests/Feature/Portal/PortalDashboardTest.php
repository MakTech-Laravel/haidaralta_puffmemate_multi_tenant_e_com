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
        'email' => UserSeeder::PLATFORM_SUPER_ADMIN,
        'password' => UserSeeder::PLATFORM_SUPER_ADMIN,
    ]);

    $response->assertRedirect('/platform/dashboard');

    $this->get('/platform/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/dashboard')
            ->where('auth.user.name', 'Platform Super Admin')
            ->where('auth.user.is_super_admin', true)
            ->where('auth.user.roles', fn ($roles) => collect($roles)->contains('super-admin'))
            ->where('portal', 'platform')
            ->where('branch', null)
        );
});

test('branch user lands on branch dashboard with branch context', function () {
    $response = $this->post(route('login.store'), [
        'email' => UserSeeder::BRANCH_1_SUPER_ADMIN,
        'password' => UserSeeder::BRANCH_1_SUPER_ADMIN,
    ]);

    $response->assertRedirect('/branch/downtown/dashboard');

    $this->get('/branch/downtown/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/dashboard')
            ->where('auth.user.name', 'Branch 1 Super Admin')
            ->where('auth.user.is_super_admin', true)
            ->where('auth.user.roles', fn ($roles) => collect($roles)->contains('super-admin'))
            ->where('portal', 'branch')
            ->where('branch.slug', 'downtown')
            ->where('branch.name', 'Downtown')
        );
});

test('driver user lands on driver dashboard', function () {
    $response = $this->post(route('login.store'), [
        'email' => UserSeeder::DRIVER,
        'password' => UserSeeder::DRIVER,
    ]);

    $response->assertRedirect('/driver/dashboard');

    $this->get('/driver/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/dashboard')
            ->where('auth.user.name', 'Driver')
            ->where('portal', 'driver')
        );
});

test('customer user lands on customer dashboard', function () {
    $response = $this->post(route('login.store'), [
        'email' => UserSeeder::CUSTOMER,
        'password' => UserSeeder::CUSTOMER,
    ]);

    $response->assertRedirect('/dashboard');

    $this->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/dashboard')
            ->where('auth.user.name', 'Customer')
            ->where('portal', 'customer')
        );
});
