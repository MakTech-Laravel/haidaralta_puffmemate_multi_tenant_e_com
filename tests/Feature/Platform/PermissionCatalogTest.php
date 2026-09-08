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
});

test('platform super admin can view the permission catalog', function () {
    $admin = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.permissions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/permissions/index')
            ->has('permissionGroups')
        );
});

test('platform staff without manage roles cannot view the permission catalog', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')
        ->get(route('platform.permissions.index'))
        ->assertForbidden();
});
