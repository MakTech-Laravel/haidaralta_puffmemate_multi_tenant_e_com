<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Gate;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform super admin can create a role with permissions', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.roles.store'), [
            'name' => 'auditor',
            'permissions' => [
                PermissionEnum::VIEW_DASHBOARD->value,
                PermissionEnum::MANAGE_ROLES->value,
            ],
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Role created.',
        ]);

    $role = Role::query()
        ->where('name', 'auditor')
        ->where('guard_name', 'platform')
        ->where(config('permission.column_names.team_foreign_key'), PlatformTeamResolver::PLATFORM_TEAM_ID)
        ->firstOrFail();

    expect($role->permissions->pluck('name')->all())
        ->toContain(PermissionEnum::VIEW_DASHBOARD->value)
        ->toContain(PermissionEnum::MANAGE_ROLES->value);
});

test('assigned custom role permissions are reflected in gate checks', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.roles.store'), [
            'name' => 'viewer',
            'permissions' => [PermissionEnum::VIEW_DASHBOARD->value],
        ])
        ->assertRedirect();

    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole('viewer');

    expect(Gate::forUser($user)->allows(PermissionEnum::VIEW_DASHBOARD->value))->toBeTrue()
        ->and(Gate::forUser($user)->allows(PermissionEnum::MANAGE_ROLES->value))->toBeFalse();
});

test('platform super admin cannot rename or delete the super admin role', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $role = Role::query()
        ->where('name', RoleEnum::SUPER_ADMIN->value)
        ->where('guard_name', 'platform')
        ->where(config('permission.column_names.team_foreign_key'), PlatformTeamResolver::PLATFORM_TEAM_ID)
        ->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.roles.update', $role), [
            'name' => 'not-super-admin',
            'permissions' => [],
        ])
        ->assertSessionHasErrors('name');

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.roles.destroy', $role))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'The super-admin role cannot be deleted.',
        ]);

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('platform role with assigned users cannot be deleted', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.roles.store'), [
            'name' => 'assigned-role',
            'permissions' => [PermissionEnum::VIEW_DASHBOARD->value],
        ])
        ->assertRedirect();

    $role = Role::query()
        ->where('name', 'assigned-role')
        ->where('guard_name', 'platform')
        ->firstOrFail();

    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole($role);

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.roles.destroy', $role))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'This role still has assigned users and cannot be deleted.',
        ]);

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('platform staff without manage roles cannot access role routes', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.roles.index'))
        ->assertForbidden();
});

test('platform roles index supports filters and show includes coverage', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.roles.store'), [
            'name' => 'ops-custom',
            'permissions' => [PermissionEnum::VIEW_DASHBOARD->value],
        ])
        ->assertRedirect();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.roles.index', [
            'filter' => [
                'search' => 'ops',
                'kind' => 'custom',
                'users' => 'without',
                'permissions' => 'some',
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/roles/index')
            ->where('table.filters.search', 'ops')
            ->where('table.filters.kind', 'custom')
            ->has('table.data'));

    $role = Role::query()->where('name', 'ops-custom')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.roles.show', $role))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/roles/show')
            ->has('permissionGroups')
            ->has('role.users'));
});
