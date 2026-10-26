<?php

use App\Enums\PermissionEnum;
use App\Support\PlatformPermissionProbe;
use App\Support\PortalAuth;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('platform users with view-dashboard land on the dashboard after login', function () {
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->post(route('login.store'), [
        'phone' => UserSeeder::PLATFORM_SUPER_ADMIN_PHONE,
        'password' => UserSeeder::DEFAULT_PASSWORD,
    ])->assertRedirect('/platform/dashboard');
});

test('catalog-only probe users land on products instead of a forbidden dashboard', function () {
    $user = PlatformPermissionProbe::ensureUser(PermissionEnum::MANAGE_CATALOG);

    expect(PortalAuth::dashboardPath($user))->toBe('/platform/products');

    $this->post(route('login.store'), [
        'phone' => PlatformPermissionProbe::phoneFor(PermissionEnum::MANAGE_CATALOG),
        'password' => PlatformPermissionProbe::PASSWORD,
    ])->assertRedirect('/platform/products');
});

test('orders probe users land on orders when they lack view-dashboard', function () {
    $user = PlatformPermissionProbe::ensureUser(PermissionEnum::MANAGE_ORDERS);

    expect(PortalAuth::dashboardPath($user))->toBe('/platform/orders');
});
