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
    $admin = User::query()->where('email', 'branch@test.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->post(route('branch.staff.store', $downtown), [
            'name' => 'Downtown Clerk',
            'email' => 'downtown-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => RoleEnum::STAFF->value,
            'is_active' => true,
        ])
        ->assertRedirect(route('branch.staff.index', $downtown));

    $clerk = User::query()->where('email', 'downtown-clerk@test.com')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::BRANCH)
        ->and($clerk->branch_id)->toBe($downtown->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);
    expect($clerk->hasRole(RoleEnum::STAFF->value))->toBeTrue();

    $this->actingAs($admin, 'branch')
        ->put(route('branch.staff.update', [$downtown, $clerk]), [
            'name' => 'Downtown Manager',
            'email' => 'downtown-clerk@test.com',
            'role' => RoleEnum::MANAGER->value,
            'is_active' => true,
        ])
        ->assertRedirect(route('branch.staff.index', $downtown));

    $clerk->refresh();
    app(PermissionRegistrar::class)->setPermissionsTeamId($downtown->id);

    expect($clerk->name)->toBe('Downtown Manager')
        ->and($clerk->hasRole(RoleEnum::MANAGER->value))->toBeTrue();
});

test('branch admin cannot manage staff from another branch by url', function () {
    $admin = User::query()->where('email', 'branch@test.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();
    $uptown = Branch::query()->where('slug', 'uptown')->firstOrFail();

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
            'email' => 'uptown-only@test.com',
            'role' => RoleEnum::ADMIN->value,
            'is_active' => true,
        ])
        ->assertNotFound();
});

test('deleting the last branch super admin is blocked', function () {
    $admin = User::query()->where('email', 'branch@test.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->delete(route('branch.staff.destroy', [$downtown, $admin]))
        ->assertSessionHasErrors('role');

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('deactivating the last branch super admin is blocked', function () {
    $admin = User::query()->where('email', 'branch@test.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'branch')
        ->put(route('branch.staff.update', [$downtown, $admin]), [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => RoleEnum::SUPER_ADMIN->value,
            'is_active' => false,
        ])
        ->assertSessionHasErrors('role');

    expect($admin->fresh()->is_active)->toBeTrue();
});
