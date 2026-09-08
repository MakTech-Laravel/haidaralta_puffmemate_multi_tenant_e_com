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

test('platform super admin can create a branch with required super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $icon = UploadedFile::fake()->image('midtown.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.branches.store'), [
            'name' => 'Midtown',
            'slug' => 'midtown',
            'icon' => $icon,
            'address' => '789 Center St',
            'is_active' => true,
            'super_admin_name' => 'Midtown Super Admin',
            'super_admin_email' => 'midtown.superadmin@test.com',
            'super_admin_password' => 'password',
            'super_admin_password_confirmation' => 'password',
        ])
        ->assertRedirect(route('platform.branches.show', 'midtown'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Branch created.',
        ]);

    $branch = Branch::query()->where('slug', 'midtown')->firstOrFail();

    expect($branch->name)->toBe('Midtown')
        ->and($branch->icon)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->icon);

    $superAdmin = User::query()->where('email', 'midtown.superadmin@test.com')->firstOrFail();

    expect($superAdmin->user_type)->toBe(UserType::BRANCH)
        ->and($superAdmin->branch_id)->toBe($branch->id);

    app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    expect($superAdmin->hasRole(RoleEnum::SUPER_ADMIN->value))->toBeTrue();
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/index')
            ->has('branches.data'));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.show', 'midtown'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/show')
            ->where('branch.slug', 'midtown')
            ->has('branch.super_admins', 1)
            ->where('branch.super_admins.0.email', 'midtown.superadmin@test.com'));
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
            'super_admin_name' => 'Nope',
            'super_admin_email' => 'nope@test.com',
            'super_admin_password' => 'password',
            'super_admin_password_confirmation' => 'password',
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
        'icon' => null,
    ]);

    $icon = UploadedFile::fake()->image('new-name.png', 120, 120);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.branches.update', $branch), [
            'name' => 'New Name',
            'slug' => 'new-name',
            'icon' => $icon,
            'is_active' => true,
        ])
        ->assertRedirect(route('platform.branches.show', 'new-name'));

    $branch->refresh();

    expect($branch->name)->toBe('New Name')
        ->and($branch->slug)->toBe('new-name')
        ->and($branch->icon)->not->toBeNull();

    Storage::disk('public')->assertExists($branch->icon);
});
