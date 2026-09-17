<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformTeamResolver;
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

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform super admin can create and view a platform admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.admins.store'), [
            'name' => 'Platform Clerk',
            'phone' => '0412987654',
            'email' => 'platform-clerk@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Admin created.',
        ]);

    $clerk = User::query()->where('phone', '+61412987654')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::PLATFORM)
        ->and($clerk->branch_id)->toBeNull();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.admins.show', $clerk))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/admins/show')
            ->has('admin.effective_permissions')
            ->has('admin.permission_groups')
            ->where('admin.avatar_url', null));
});

test('platform super admin can create an admin with an avatar', function () {
    Storage::fake('public');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.admins.store'), [
            'name' => 'Avatar Admin',
            'phone' => '0412987655',
            'email' => 'avatar-admin@test.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ])
        ->assertRedirect();

    $created = User::query()->where('phone', '+61412987655')->firstOrFail();

    expect($created->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($created->avatar);
});

test('platform super admin can update an admin avatar', function () {
    Storage::fake('public');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $clerk = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'branch_id' => null,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $clerk->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.admins.update', $clerk), [
            'name' => $clerk->name,
            'phone' => $clerk->phone,
            'email' => $clerk->email,
            'roles' => [RoleEnum::STAFF->value],
            'is_active' => true,
            'avatar' => UploadedFile::fake()->image('new-avatar.jpg'),
        ])
        ->assertRedirect();

    $clerk->refresh();

    expect($clerk->avatar)->not->toBeNull();
    Storage::disk('public')->assertExists($clerk->avatar);
});

test('cannot delete the last platform super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.admins.destroy', $admin))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot remove or deactivate the last platform super-admin.',
        ]);

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('platform staff without manage users cannot access admin routes', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.admins.index'))
        ->assertForbidden();
});

test('platform admin show 404s for a non platform user', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branchUser = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.admins.show', $branchUser))
        ->assertNotFound();
});

test('old platform staff urls redirect to admins', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get('/platform/staff')
        ->assertMovedPermanently()
        ->assertRedirect('/platform/admins');
});
