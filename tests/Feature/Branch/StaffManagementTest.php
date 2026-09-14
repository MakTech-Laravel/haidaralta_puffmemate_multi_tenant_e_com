<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
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

test('branch admin can create and update staff in their own branch', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.staff.store', $downtown), [
            'name' => 'Sydney Floor Clerk',
            'phone' => '0412000002',
            'email' => 'sydney-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
        ])
        ->assertRedirect(route('branch.staff.show', [
            $downtown,
            User::query()->where('email', 'sydney-clerk@test.com')->firstOrFail(),
        ]))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Staff member created.',
        ]);

    $clerk = User::query()->where('email', 'sydney-clerk@test.com')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::BRANCH)
        ->and($clerk->branch_id)->toBe($downtown->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    expect($clerk->hasRole(RoleEnum::STAFF->value))->toBeTrue();

    $this->actingAs($admin, 'branch')
        ->put(route('branch.staff.update', [$downtown, $clerk]), [
            'name' => 'Sydney Shift Lead',
            'phone' => '0412000002',
            'email' => 'sydney-clerk@test.com',
            'roles' => [RoleEnum::MANAGER->value],
            'is_active' => true,
        ])
        ->assertRedirect(route('branch.staff.show', [$downtown, $clerk]));

    $clerk->refresh();
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $clerk->unsetRelation('roles');

    expect($clerk->name)->toBe('Sydney Shift Lead')
        ->and($clerk->hasRole(RoleEnum::MANAGER->value))->toBeTrue();
});

test('branch admin cannot manage staff from another branch by url', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $uptown = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $uptownStaff = User::factory()->create([
        'email' => 'uptown-only@test.com',
        'user_type' => UserType::BRANCH,
        'branch_id' => $uptown->id,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($uptown->id);
    $uptownStaff->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.staff.index', $uptown))
        ->assertForbidden();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.staff.edit', [$downtown, $uptownStaff]))
        ->assertNotFound();

    $this->actingAs($admin, 'branch')
        ->put(route('branch.staff.update', [$downtown, $uptownStaff]), [
            'name' => 'Hacked',
            'phone' => $uptownStaff->phone,
            'email' => 'uptown-only@test.com',
            'roles' => [RoleEnum::ADMIN->value],
            'is_active' => true,
        ])
        ->assertNotFound();
});

test('deleting the last branch super admin is blocked', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->delete(route('branch.staff.destroy', [$downtown, $admin]))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot remove or deactivate the last super-admin for this branch.',
        ]);

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('deactivating the last branch super admin is blocked', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->put(route('branch.staff.update', [$downtown, $admin]), [
            'name' => $admin->name,
            'phone' => $admin->phone,
            'email' => $admin->email,
            'roles' => [RoleEnum::SUPER_ADMIN->value],
            'is_active' => false,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot remove or deactivate the last super-admin for this branch.',
        ]);

    expect($admin->fresh()->is_active)->toBeTrue();
});

test('branch staff show page includes effective permissions', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->get(route('branch.staff.show', [$downtown, $admin]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/staff/show')
            ->has('staffMember.effective_permissions'));
});

test('branch staff index can search by phone', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $clerk = User::factory()->create([
        'name' => 'Searchable Clerk',
        'phone' => '+61412001122',
        'user_type' => UserType::BRANCH,
        'branch_id' => $downtown->id,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    $clerk->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'branch')
        ->get(route('branch.staff.index', [
            $downtown,
            'filter' => ['search' => '412001122'],
        ]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/staff/index')
            ->where('table.filters.search', '412001122')
            ->has('table.data', 1)
            ->where('table.data.0.phone', '+61412001122')
        );
});
