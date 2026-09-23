<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Role;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use App\Support\SuperAdmin;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can create update and delete branch staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Platform Created Clerk',
            'phone' => '0412000001',
            'email' => 'platform-created-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch staff member created.',
        ]);

    $clerk = User::query()->where('email', 'platform-created-clerk@test.com')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::BRANCH)
        ->and($clerk->branch_id)->toBe($downtown->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    expect($clerk->hasRole(RoleEnum::STAFF->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branch-staff.update', $clerk), [
            'name' => 'Platform Updated Clerk',
            'phone' => '0412000001',
            'email' => 'platform-created-clerk@test.com',
            'roles' => [RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.branch-staff.show', $clerk));

    $clerk->refresh();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $clerk->unsetRelation('roles');

    expect($clerk->name)->toBe('Platform Updated Clerk')
        ->and($clerk->getRoleNames()->all())->toBe([RoleEnum::MANAGER->value]);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.branch-staff.destroy', $clerk))
        ->assertRedirect(route('platform.branch-staff.index'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch staff member deleted.',
        ]);

    expect(User::query()->whereKey($clerk->id)->exists())->toBeFalse();
});

test('platform admin with manage-branch-users can create branch staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Admin Created Clerk',
            'phone' => '0412000004',
            'email' => 'admin-created-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(User::query()->where('email', 'admin-created-clerk@test.com')->exists())->toBeTrue();
});

test('platform branch staff store rejects more than one role', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Two Roles Clerk',
            'phone' => '0412000007',
            'email' => 'two-roles-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value, RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertInvalid(['roles']);

    expect(User::query()->where('email', 'two-roles-clerk@test.com')->exists())->toBeFalse();
});

test('platform manager without manage-branch-users cannot access branch staff routes', function () {
    $manager = User::query()->where('email', UserSeeder::PLATFORM_MANAGER)->firstOrFail();

    $this->actingAs($manager, 'platform')
        ->get(route('platform.branch-staff.index'))
        ->assertForbidden();

    $this->actingAs($manager, 'platform')
        ->get(route('platform.branch-staff.create'))
        ->assertForbidden();
});

test('platform staff without manage-branch-users cannot access branch staff routes', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.branch-staff.index'))
        ->assertForbidden();
});

test('granting manage-branch-users to a custom platform role unlocks branch staff management', function () {
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);

    $customRole = Role::query()->create([
        'name' => 'branch-user-ops',
        'guard_name' => 'platform',
        config('permission.column_names.team_foreign_key') => PlatformTeamResolver::PLATFORM_TEAM_ID,
    ]);
    $customRole->givePermissionTo([
        PermissionEnum::VIEW_DASHBOARD->value,
        PermissionEnum::MANAGE_BRANCH_USERS->value,
    ]);

    $operator = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);
    $operator->assignRole($customRole);

    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($operator, 'platform')
        ->get(route('platform.branch-staff.index'))
        ->assertOk();

    $this->actingAs($operator, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Ops Created Clerk',
            'phone' => '0412000005',
            'email' => 'ops-created-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
        ])
        ->assertRedirect();

    expect(User::query()->where('email', 'ops-created-clerk@test.com')->exists())->toBeTrue();
});

test('only platform super admin can assign branch super-admin role', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Should Fail Super',
            'phone' => '0412000006',
            'email' => 'should-fail-super@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::SUPER_ADMIN->value],
            'is_active' => true,
        ])
        ->assertSessionHasErrors('roles');

    expect(User::query()->where('email', 'should-fail-super@test.com')->exists())->toBeFalse();
});

test('platform cannot delete the last branch super admin', function () {
    $platform = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchSuper = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($platform, 'platform')
        ->delete(route('platform.branch-staff.destroy', $branchSuper))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => SuperAdmin::LAST_ACTIVE_BRANCH_MESSAGE,
        ]);

    expect(User::query()->whereKey($branchSuper->id)->exists())->toBeTrue();
});

test('platform can paginate branch and role options for staff form', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.branch-staff.branch-options', [
            'per_page' => 20,
            'filter' => ['search' => 'Sydney'],
        ]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug']],
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ])
        ->assertJsonPath('data.0.id', $downtown->id);

    $this->actingAs($admin, 'platform')
        ->getJson(route('platform.branch-staff.role-options', [
            'branch_id' => $downtown->id,
        ]))
        ->assertOk()
        ->assertJsonStructure([
            'data' => [['value', 'label']],
        ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/create')
            ->missing('branches')
            ->missing('rolesByBranch'));
});

test('platform super admin can create and update branch staff with an avatar', function () {
    Storage::fake('public');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branch-staff.store'), [
            'branch_id' => $downtown->id,
            'name' => 'Avatar Clerk',
            'phone' => '0412000099',
            'email' => 'avatar-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('staff-avatar.jpg'),
        ])
        ->assertRedirect();

    $clerk = User::query()->where('email', 'avatar-clerk@test.com')->firstOrFail();

    expect($clerk->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($clerk->avatar);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branch-staff.show', $clerk))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branch-staff/show')
            ->where('staffMember.avatar_url', fn ($url) => is_string($url) && $url !== '')
            ->has('staffMember.permission_groups')
            ->missing('branches')
            ->missing('rolesByBranch'));

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branch-staff.update', $clerk), [
            'name' => 'Avatar Clerk Updated',
            'phone' => '0412000099',
            'email' => 'avatar-clerk@test.com',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('staff-avatar-2.jpg'),
        ])
        ->assertRedirect(route('platform.branch-staff.show', $clerk));

    $clerk->refresh();

    expect($clerk->name)->toBe('Avatar Clerk Updated')
        ->and($clerk->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($clerk->avatar);
});
