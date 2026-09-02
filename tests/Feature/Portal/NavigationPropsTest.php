<?php

use App\Enums\UserType;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
});

test('user without view dashboard permission has empty permission gate for platform nav', function () {
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
