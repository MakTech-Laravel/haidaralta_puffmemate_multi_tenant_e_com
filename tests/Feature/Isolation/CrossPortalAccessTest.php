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
    'branch' => ['/branch/sydney-cbd/dashboard'],
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
    'platform' => [UserSeeder::PLATFORM_SUPER_ADMIN, 'platform', '/platform/dashboard'],
    'branch' => [UserSeeder::BRANCH_1_SUPER_ADMIN, 'branch', '/branch/sydney-cbd/dashboard'],
    'driver' => [UserSeeder::DRIVER, 'driver', '/driver/dashboard'],
    'customer' => [UserSeeder::CUSTOMER, 'customer', '/dashboard'],
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
    'platform to branch sydney-cbd' => [UserSeeder::PLATFORM_SUPER_ADMIN, 'platform', '/branch/sydney-cbd/dashboard', 302],
    'platform to driver' => [UserSeeder::PLATFORM_SUPER_ADMIN, 'platform', '/driver/dashboard', 302],
    'platform to customer' => [UserSeeder::PLATFORM_SUPER_ADMIN, 'platform', '/dashboard', 302],
    'branch to platform' => [UserSeeder::BRANCH_1_SUPER_ADMIN, 'branch', '/platform/dashboard', 302],
    'branch to driver' => [UserSeeder::BRANCH_1_SUPER_ADMIN, 'branch', '/driver/dashboard', 302],
    'branch to customer' => [UserSeeder::BRANCH_1_SUPER_ADMIN, 'branch', '/dashboard', 302],
    'driver to platform' => [UserSeeder::DRIVER, 'driver', '/platform/dashboard', 302],
    'driver to branch sydney-cbd' => [UserSeeder::DRIVER, 'driver', '/branch/sydney-cbd/dashboard', 302],
    'driver to customer' => [UserSeeder::DRIVER, 'driver', '/dashboard', 302],
    'customer to platform' => [UserSeeder::CUSTOMER, 'customer', '/platform/dashboard', 302],
    'customer to branch sydney-cbd' => [UserSeeder::CUSTOMER, 'customer', '/branch/sydney-cbd/dashboard', 302],
    'customer to driver' => [UserSeeder::CUSTOMER, 'customer', '/driver/dashboard', 302],
]);

test('branch user receives forbidden when visiting another branch dashboard', function () {
    $user = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($user, 'branch')
        ->get('/branch/melbourne-collins/dashboard')
        ->assertForbidden();
});

test('non branch users are redirected to login from alternate branch slugs', function (
    string $email,
    string $guard,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user, $guard)
        ->get('/branch/melbourne-collins/dashboard')
        ->assertRedirect(route('login'));
})->with([
    'platform' => [UserSeeder::PLATFORM_SUPER_ADMIN, 'platform'],
    'driver' => [UserSeeder::DRIVER, 'driver'],
    'customer' => [UserSeeder::CUSTOMER, 'customer'],
]);

test('inactive portal user is logged out and redirected to login', function () {
    $user = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $user->update(['is_active' => false]);

    $response = $this->actingAs($user, 'platform')->get('/platform/dashboard');

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('phone');

    $this->assertGuest('platform');
});
