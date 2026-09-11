<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
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

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform super admin can create and view platform staff', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.staff.store'), [
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
            'message' => 'Staff member created.',
        ]);

    $clerk = User::query()->where('phone', '+61412987654')->firstOrFail();

    expect($clerk->user_type)->toBe(UserType::PLATFORM)
        ->and($clerk->branch_id)->toBeNull();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.staff.show', $clerk))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/staff/show')
            ->has('staffMember.effective_permissions'));
});

test('cannot delete the last platform super admin', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->delete(route('platform.staff.destroy', $admin))
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Cannot remove or deactivate the last platform super-admin.',
        ]);

    expect(User::query()->whereKey($admin->id)->exists())->toBeTrue();
});

test('platform staff without manage users cannot access staff routes', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.staff.index'))
        ->assertForbidden();
});
