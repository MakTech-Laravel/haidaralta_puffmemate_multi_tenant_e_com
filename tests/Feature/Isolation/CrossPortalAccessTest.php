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

test('guests are redirected to login from all portal dashboards', function (string $url) {
    $this->get($url)->assertRedirect(route('login'));
})->with([
    'platform' => ['/platform/dashboard'],
    'branch' => ['/branch/downtown/dashboard'],
    'driver' => ['/driver/dashboard'],
    'customer' => ['/dashboard'],
]);

test('authenticated users can access their own portal dashboard', function (
    string $email,
    string $guard,
    string $dashboardUrl,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user, $guard)
        ->get($dashboardUrl)
        ->assertOk();
})->with([
    'platform' => ['platform@test.com', 'platform', '/platform/dashboard'],
    'branch' => ['branch@test.com', 'branch', '/branch/downtown/dashboard'],
    'driver' => ['driver@test.com', 'driver', '/driver/dashboard'],
    'customer' => ['customer@test.com', 'customer', '/dashboard'],
]);

test('authenticated users cannot access other portal dashboards', function (
    string $email,
    string $guard,
    string $foreignDashboardUrl,
    int $expectedStatus,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $response = $this->actingAs($user, $guard)->get($foreignDashboardUrl);

    if ($expectedStatus === 302) {
        $response->assertRedirect(route('login'));
    } else {
        $response->assertStatus($expectedStatus);
    }
})->with([
    'platform to branch downtown' => ['platform@test.com', 'platform', '/branch/downtown/dashboard', 302],
    'platform to driver' => ['platform@test.com', 'platform', '/driver/dashboard', 302],
    'platform to customer' => ['platform@test.com', 'platform', '/dashboard', 302],
    'branch to platform' => ['branch@test.com', 'branch', '/platform/dashboard', 302],
    'branch to driver' => ['branch@test.com', 'branch', '/driver/dashboard', 302],
    'branch to customer' => ['branch@test.com', 'branch', '/dashboard', 302],
    'driver to platform' => ['driver@test.com', 'driver', '/platform/dashboard', 302],
    'driver to branch downtown' => ['driver@test.com', 'driver', '/branch/downtown/dashboard', 302],
    'driver to customer' => ['driver@test.com', 'driver', '/dashboard', 302],
    'customer to platform' => ['customer@test.com', 'customer', '/platform/dashboard', 302],
    'customer to branch downtown' => ['customer@test.com', 'customer', '/branch/downtown/dashboard', 302],
    'customer to driver' => ['customer@test.com', 'customer', '/driver/dashboard', 302],
]);

test('branch user receives forbidden when visiting another branch dashboard', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    $this->actingAs($user, 'branch')
        ->get('/branch/uptown/dashboard')
        ->assertForbidden();
});

test('non branch users are redirected to login from alternate branch slugs', function (
    string $email,
    string $guard,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user, $guard)
        ->get('/branch/uptown/dashboard')
        ->assertRedirect(route('login'));
})->with([
    'platform' => ['platform@test.com', 'platform'],
    'driver' => ['driver@test.com', 'driver'],
    'customer' => ['customer@test.com', 'customer'],
]);

test('inactive portal user is logged out and redirected to login', function () {
    $user = User::query()->where('email', 'platform@test.com')->firstOrFail();
    $user->update(['is_active' => false]);

    $response = $this->actingAs($user, 'platform')->get('/platform/dashboard');

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('platform');
});
