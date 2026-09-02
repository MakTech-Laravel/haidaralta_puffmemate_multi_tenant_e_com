<?php

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform dashboard shares platform scoped auth props', function () {
    $user = User::query()->where('email', 'platform@test.com')->firstOrFail();

    $this->actingAs($user, 'platform')
        ->get('/platform/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('portal', 'platform')
            ->where('branch', null)
            ->where('auth.user.is_super_admin', true)
            ->has('auth.user.roles')
            ->has('auth.user.permissions')
        );
});

test('branch dashboard shares branch scoped auth props', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    $this->actingAs($user, 'branch')
        ->get('/branch/downtown/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('portal', 'branch')
            ->where('branch.id', $user->branch_id)
            ->where('branch.slug', 'downtown')
            ->where('auth.user.is_super_admin', true)
        );
});

test('restricted platform user does not receive view dashboard permission', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($user, 'platform')
        ->get('/platform/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.user.is_super_admin', false)
            ->where('auth.user.permissions', fn ($permissions) => ! collect($permissions)->contains('view-dashboard'))
        );
});
