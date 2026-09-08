<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\TelescopeAccess;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('telescope routes are unavailable in the testing environment', function () {
    $this->get('/telescope')->assertNotFound();
});

test('platform super admin is allowed to view telescope in local environment', function () {
    $admin = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();

    app()->detectEnvironment(fn (): string => 'local');

    expect(TelescopeAccess::allows($admin))->toBeTrue();
});

test('non platform users cannot view telescope even in local environment', function () {
    $branch = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $driver = User::query()->where('email', 'driver@dev.com')->firstOrFail();
    $customer = User::query()->where('email', 'user@dev.com')->firstOrFail();

    app()->detectEnvironment(fn (): string => 'local');

    expect(TelescopeAccess::allows($branch))->toBeFalse()
        ->and(TelescopeAccess::allows($driver))->toBeFalse()
        ->and(TelescopeAccess::allows($customer))->toBeFalse();
});

test('platform staff without super admin cannot view telescope', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    app()->detectEnvironment(fn (): string => 'local');

    expect(TelescopeAccess::allows($user))->toBeFalse();
});

test('telescope access is denied outside local and staging', function () {
    $admin = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();

    app()->detectEnvironment(fn (): string => 'production');

    expect(TelescopeAccess::allows($admin))->toBeFalse();
});
