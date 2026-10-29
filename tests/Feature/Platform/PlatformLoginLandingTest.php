<?php

use App\Enums\PermissionEnum;
use App\Models\Branch;
use App\Support\BranchPermissionProbe;
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

test('platform branch chat-only probe users land on branch chats', function () {
    $user = PlatformPermissionProbe::ensureUser(PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT);

    expect(PortalAuth::dashboardPath($user))->toBe('/platform/branch-chats');
});

test('branch driver and platform chat-only probe users land on the matching chat inbox', function () {
    $this->seed(BranchSeeder::class);
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $driverChatUser = BranchPermissionProbe::ensureUser(
        $branch,
        PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT,
    );
    $platformChatUser = BranchPermissionProbe::ensureUser(
        $branch,
        PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT,
    );

    expect(PortalAuth::dashboardPath($driverChatUser))->toBe('/branch/'.$branch->slug.'/driver-chats')
        ->and(PortalAuth::dashboardPath($platformChatUser))->toBe('/branch/'.$branch->slug.'/platform-chats');
});
