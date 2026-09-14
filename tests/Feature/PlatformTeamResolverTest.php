<?php

use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\Exceptions\RoleAlreadyExists;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform team resolver defaults to reserved team id zero', function () {
    expect((new PlatformTeamResolver)->getPermissionsTeamId())->toBe(0)
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(0);
});

test('platform super admin role is scoped to team id zero', function () {
    expect(Role::query()
        ->where('name', RoleEnum::SUPER_ADMIN->value)
        ->where('guard_name', 'platform')
        ->value('branch_id'))->toBe(0);
});

test('platform user has super admin role without explicit team context', function () {
    $platformUser = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->first();

    expect($platformUser)->not->toBeNull()
        ->and($platformUser->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
});

test('branch user has super admin role when branch team context is set', function () {
    $branchUser = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->first();
    $branchId = $branchUser->branch_id;

    app(PermissionRegistrar::class)->setPermissionsTeamId($branchId);

    expect($branchUser->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
});

test('duplicate platform super admin role is rejected', function () {
    expect(fn () => Role::create([
        'name' => RoleEnum::SUPER_ADMIN->value,
        'guard_name' => 'platform',
    ]))->toThrow(RoleAlreadyExists::class);
});

test('branch roles are scoped to their branch team id', function () {
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    expect(Role::query()
        ->where('name', RoleEnum::SUPER_ADMIN->value)
        ->where('guard_name', 'branch')
        ->where('branch_id', $downtown->id)
        ->exists())->toBeTrue();
});
