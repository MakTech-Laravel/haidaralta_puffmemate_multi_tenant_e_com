<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
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

test('platform super admin can list and create staff for a nested branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.staff.index', $downtown))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/staff/index')
            ->where('branch.slug', 'downtown')
        );

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.staff.store', $downtown), [
            'name' => 'Scoped Clerk',
            'phone' => '0412000003',
            'email' => 'scoped-clerk@test.com',
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

    $clerk = User::query()->where('email', 'scoped-clerk@test.com')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::BRANCH)
        ->and($clerk->branch_id)->toBe($downtown->id);
});

test('nested branch staff routes return 404 for staff of another branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

    $uptownStaff = User::factory()->create([
        'email' => 'uptown-scoped@test.com',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($uptown->id);
    $uptownStaff->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.staff.show', [$downtown, $uptownStaff]))
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.staff.edit', [$downtown, $uptownStaff]))
        ->assertNotFound();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.staff.update', [$downtown, $uptownStaff]), [
            'name' => 'Hacked',
            'phone' => $uptownStaff->phone,
            'email' => 'uptown-scoped@test.com',
            'roles' => [RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertNotFound();
});

test('platform user without manage-branch-users cannot access nested branch staff', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($user, 'platform')
        ->get(route('platform.branches.staff.index', $downtown))
        ->assertForbidden();
});

test('platform admin can update and delete nested branch staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $clerk = User::factory()->create([
        'name' => 'Nested Clerk',
        'email' => 'nested-clerk@test.com',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $clerk->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.staff.update', [$downtown, $clerk]), [
            'name' => 'Nested Manager',
            'phone' => $clerk->phone,
            'email' => 'nested-clerk@test.com',
            'roles' => [RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.branches.staff.show', [$downtown, $clerk]));

    $clerk->refresh();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $clerk->unsetRelation('roles');

    expect($clerk->name)->toBe('Nested Manager')
        ->and($clerk->getRoleNames()->all())->toBe([RoleEnum::MANAGER->value]);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.branches.staff.destroy', [$downtown, $clerk]))
        ->assertRedirect(route('platform.branches.staff.index', $downtown));

    expect(User::query()->whereKey($clerk->id)->exists())->toBeFalse();
});

test('nested branch staff index can search by name', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $match = User::factory()->create([
        'name' => 'Nested Zelda Clerk',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
    ]);

    User::factory()->create([
        'name' => 'Nested Other Clerk',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $match->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.staff.index', [
            $downtown,
            'filter' => ['search' => 'Nested Zelda'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/staff/index')
            ->where('table.filters.search', 'Nested Zelda')
            ->has('table.data', 1)
            ->where('table.data.0.name', 'Nested Zelda Clerk')
        );
});
