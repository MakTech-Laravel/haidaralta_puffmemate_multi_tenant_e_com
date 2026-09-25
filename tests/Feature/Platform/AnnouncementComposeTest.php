<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform admin can open the announcement compose page', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.announcements.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/announcements/index'));
});

test('platform staff without manage-reports cannot open announcements', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $this->actingAs($user, 'platform')
        ->get(route('platform.announcements.index'))
        ->assertForbidden();
});

test('guests are redirected from announcements', function () {
    $this->get(route('platform.announcements.index'))
        ->assertRedirect(route('login'));
});
