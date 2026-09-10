<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    Storage::fake('public');

    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can create a branch then add super admin in step two', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $logo = UploadedFile::fake()->image('midtown.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), [
            'name' => 'Midtown',
            'slug' => 'midtown',
            'code' => 'MID-001',
            'logo' => $logo,
            'phone' => '+1-555-0100',
            'email' => 'midtown@test.com',
            'address' => '789 Center St',
            'opening_time' => '09:00',
            'closing_time' => '21:00',
            'status' => true,
        ])
        ->assertRedirect(route('platform.branches.super-admin.create', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch created. Add a super-admin to finish setup.',
        ]);

    $branch = Branch::query()->where('slug', 'midtown')->firstOrFail();

    expect($branch->name)->toBe('Midtown')
        ->and($branch->code)->toBe('MID-001')
        ->and($branch->status)->toBeTrue()
        ->and($branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->logo);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.super-admin.create', 'midtown'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/create-super-admin')
            ->where('branch.slug', 'midtown'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.super-admin.store', 'midtown'), [
            'name' => 'Midtown Super Admin',
            'email' => 'midtown.superadmin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch super-admin created.',
        ]);

    $superAdmin = User::query()->where('email', 'midtown.superadmin@test.com')->firstOrFail();

    expect($superAdmin->user_type)->toBe(UserType::BRANCH)
        ->and($superAdmin->branch_id)->toBe($branch->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    expect($superAdmin->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', 'midtown'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('branch.slug', 'midtown')
            ->where('branch.needs_super_admin', false)
            ->has('branch.super_admins', 1)
            ->where('branch.super_admins.0.email', 'midtown.superadmin@test.com'));
});

test('cannot create a second branch super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.super-admin.store', $downtown), [
            'name' => 'Another Super',
            'email' => 'another.super@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', $downtown))
        ->assertInertiaFlash('toast', [
            'type' => 'warning',
            'message' => 'This branch already has a super-admin.',
        ]);

    expect(User::query()->where('email', 'another.super@test.com')->exists())->toBeFalse();
});

test('platform staff without create branches cannot create a branch', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->post(route('platform.branches.store'), [
            'name' => 'Blocked',
            'slug' => 'blocked',
            'code' => 'BLK-001',
        ])
        ->assertForbidden();
});

test('cannot delete a branch that still has staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.branches.destroy', $downtown))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot delete a branch that still has staff accounts.',
        ]);

    expect(Branch::query()->whereKey($downtown->id)->exists())->toBeTrue();
});

test('platform admin can update a branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::factory()->create([
        'name' => 'Old Name',
        'slug' => 'old-name',
        'code' => 'OLD-001',
        'logo' => null,
    ]);

    $logo = UploadedFile::fake()->image('new-name.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.update', $branch), [
            'name' => 'New Name',
            'slug' => 'new-name',
            'code' => 'NEW-001',
            'logo' => $logo,
            'phone' => '+1-555-9999',
            'opening_time' => '08:00',
            'closing_time' => '20:00',
            'status' => true,
        ])
        ->assertRedirect(route('platform.branches.show', 'new-name'));

    $branch->refresh();

    expect($branch->name)->toBe('New Name')
        ->and($branch->slug)->toBe('new-name')
        ->and($branch->code)->toBe('NEW-001')
        ->and($branch->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->logo);
});
