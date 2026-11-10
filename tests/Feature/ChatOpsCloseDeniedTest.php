<?php

use App\Enums\ConversationType;
use App\Models\Branch;
use App\Models\User;
use App\Services\Conversation\ConversationService;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('driver ops chats cannot be closed', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $branchStaff = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $service = app(ConversationService::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    $driverOps = $service->openDriverOps($branch, $driver, $branchStaff);

    $this->actingAs($branchStaff, 'branch')
        ->post(route('branch.driver-chats.close', [$branch, $driverOps]))
        ->assertForbidden();

    $driverOps->refresh();

    expect($driverOps->type)->toBe(ConversationType::DriverOps)
        ->and($driverOps->status->value)->toBe('open');
});

test('platform branch chats cannot be closed from either portal', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $branchStaff = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $platformStaff = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $service = app(ConversationService::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $platformBranch = $service->openPlatformBranch($branch, $platformStaff);

    $this->actingAs($platformStaff, 'platform')
        ->post(route('platform.branch-chats.close', $platformBranch))
        ->assertForbidden();

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);

    $this->actingAs($branchStaff, 'branch')
        ->post(route('branch.platform-chats.close', [$branch, $platformBranch]))
        ->assertForbidden();

    $platformBranch->refresh();

    expect($platformBranch->type)->toBe(ConversationType::PlatformBranch)
        ->and($platformBranch->status->value)->toBe('open');
});

test('driver chats index redirects to the sole open thread', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $service = app(ConversationService::class);

    $conversation = $service->openDriverOps($branch, $driver, $driver);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.chats.index'))
        ->assertRedirect(route('driver.chats.show', $conversation));
});

test('branch platform chats index redirects to the sole open thread', function () {
    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_ADMIN)->firstOrFail();
    $service = app(ConversationService::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    $conversation = $service->openPlatformBranch($branch, $staff);

    $this->actingAs($staff, 'branch')
        ->get(route('branch.platform-chats.index', $branch))
        ->assertRedirect(route('branch.platform-chats.show', [$branch, $conversation]));
});
