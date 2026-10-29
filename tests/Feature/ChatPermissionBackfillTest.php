<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Models\Permission;
use App\Models\Role;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Spatie\Permission\PermissionRegistrar;

test('re-running roles seeder attaches new chat permissions to existing platform and branch roles', function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);

    $branch = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $teamKey = config('permission.column_names.team_foreign_key');

    Permission::query()
        ->whereIn('name', [
            PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value,
            PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT->value,
            PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value,
        ])
        ->delete();

    app(PermissionRegistrar::class)->forgetCachedPermissions();

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $platformAdmin = Role::query()
        ->where('name', RoleEnum::ADMIN->value)
        ->where('guard_name', 'platform')
        ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID)
        ->firstOrFail();
    $platformAdmin->syncPermissions(
        $platformAdmin->permissions
            ->reject(fn (Permission $permission): bool => $permission->name === PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value)
            ->all(),
    );

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    $branchAdmin = Role::query()
        ->where('name', RoleEnum::ADMIN->value)
        ->where('guard_name', 'branch')
        ->where($teamKey, $branch->id)
        ->firstOrFail();
    $branchAdmin->syncPermissions(
        $branchAdmin->permissions
            ->reject(fn (Permission $permission): bool => in_array($permission->name, [
                PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT->value,
                PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value,
            ], true))
            ->all(),
    );

    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->seed(RolesAndPermissionsSeeder::class);

    expect(Permission::query()->where('name', PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value)->where('guard_name', 'platform')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT->value)->where('guard_name', 'branch')->exists())->toBeTrue()
        ->and(Permission::query()->where('name', PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value)->where('guard_name', 'branch')->exists())->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $platformAdmin->refresh();
    expect($platformAdmin->hasPermissionTo(PermissionEnum::MANAGE_PLATFORM_BRANCH_CHAT->value))->toBeTrue();

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    $branchAdmin->refresh();
    expect($branchAdmin->hasPermissionTo(PermissionEnum::MANAGE_BRANCH_DRIVER_CHAT->value))->toBeTrue()
        ->and($branchAdmin->hasPermissionTo(PermissionEnum::MANAGE_BRANCH_PLATFORM_CHAT->value))->toBeTrue();
});
