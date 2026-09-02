<?php

use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('logging out ends the active guard session and blocks dashboard access', function (
    string $email,
    string $guard,
    string $dashboardUrl,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user, $guard)
        ->post(route('logout'))
        ->assertRedirect(route('login'));

    $this->assertGuest($guard);

    $this->get($dashboardUrl)->assertRedirect(route('login'));
})->with([
    'platform' => ['platform@test.com', 'platform', '/platform/dashboard'],
    'branch' => ['branch@test.com', 'branch', '/branch/downtown/dashboard'],
    'driver' => ['driver@test.com', 'driver', '/driver/dashboard'],
    'customer' => ['customer@test.com', 'customer', '/dashboard'],
]);
