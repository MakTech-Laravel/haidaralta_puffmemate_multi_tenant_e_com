<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('branch super admin can create a branch role with permissions', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.roles.store', $downtown), [
            'name' => 'branch-auditor',
            'permissions' => [
                PermissionEnum::VIEW_BRANCH_DASHBOARD->value,
                PermissionEnum::MANAGE_BRANCH_ORDERS->value,
            ],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Role created.',
        ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);

    $role = Role::query()
        ->where('name', 'branch-auditor')
        ->where('guard_name', 'branch')
        ->where(config('permission.column_names.team_foreign_key'), $downtown->id)
        ->firstOrFail();

    expect($role->permissions->pluck('name')->all())
        ->toContain(PermissionEnum::VIEW_BRANCH_DASHBOARD->value)
        ->toContain(PermissionEnum::MANAGE_BRANCH_ORDERS->value);
});

test('branch role store rejects a duplicate name', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $teamKey = config('permission.column_names.team_foreign_key');
    $before = Role::query()
        ->where('guard_name', 'branch')
        ->where($teamKey, $downtown->id)
        ->count();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.roles.store', $downtown), [
            'name' => RoleEnum::STAFF->value,
            'permissions' => [PermissionEnum::VIEW_BRANCH_DASHBOARD->value],
        ])
        ->assertInvalid([
            'name' => 'A role with this name already exists.',
        ]);

    expect(Role::query()
        ->where('guard_name', 'branch')
        ->where($teamKey, $downtown->id)
        ->count())->toBe($before);
});

test('branch role cannot be deleted when users are assigned', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.roles.store', $downtown), [
            'name' => 'assigned-branch-role',
            'permissions' => [PermissionEnum::VIEW_BRANCH_DASHBOARD->value],
        ])
        ->assertRedirect();

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $role = Role::query()->where('name', 'assigned-branch-role')->firstOrFail();
    $admin->assignRole($role);

    $this->actingAs($admin, 'branch')
        ->delete(route('branch.roles.destroy', [$downtown, $role]))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'This role still has assigned users and cannot be deleted.',
        ]);
});

test('branch staff without manage branch roles cannot access role routes', function () {
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $user = User::factory()->create([
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'branch')
        ->get(route('branch.roles.index', $downtown))
        ->assertForbidden();
});

test('branch roles index loads the scoped data table for branch super admin', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.roles.index', $downtown))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/roles/index')
            ->where('branch.slug', BranchSeeder::SYDNEY_CBD)
            ->has('table.data'));
});

test('branch permissions catalog is readable by branch super admin', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.permissions.index', $downtown))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/permissions/index')
            ->has('permissionGroups')
            ->where(
                'permissionGroups',
                function ($groups): bool {
                    $names = collect($groups)->pluck('group')->all();
                    $sorted = $names;
                    natcasesort($sorted);

                    return array_values($names) === array_values($sorted);
                },
            ));
});
