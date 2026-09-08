<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin bypasses gate checks without explicit team context', function () {
    $platformUser = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe(0)
        ->and($platformUser->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue()
        ->and(Gate::forUser($platformUser)->allows('nonexistent-ability'))->toBeTrue();
});

test('platform staff role does not bypass gate checks', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(0);
    $user->assignRole(RoleEnum::STAFF->value);

    expect($user->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeFalse()
        ->and(Gate::forUser($user)->allows('nonexistent-ability'))->toBeFalse();
});

test('branch super admin bypasses gate checks only within their branch team context', function () {
    $branchUser = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();
    $branchId = $branchUser->branch_id;

    app(PermissionRegistrar::class)->setPermissionsTeamId($branchId);

    expect($branchUser->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue()
        ->and(Gate::forUser($branchUser)->allows('nonexistent-ability'))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId(0);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $branchUser->unsetRelation('roles');
    $branchUser->unsetRelation('permissions');

    expect($branchUser->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeFalse()
        ->and(Gate::forUser($branchUser)->allows('nonexistent-ability'))->toBeFalse();
});
