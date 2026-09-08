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
    'platform' => ['superadmin@dev.com', 'platform', '/platform/dashboard'],
    'branch' => ['branch1.superadmin@dev.com', 'branch', '/branch/downtown/dashboard'],
    'driver' => ['driver@dev.com', 'driver', '/driver/dashboard'],
    'customer' => ['user@dev.com', 'customer', '/dashboard'],
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
    'platform to branch downtown' => ['superadmin@dev.com', 'platform', '/branch/downtown/dashboard', 302],
    'platform to driver' => ['superadmin@dev.com', 'platform', '/driver/dashboard', 302],
    'platform to customer' => ['superadmin@dev.com', 'platform', '/dashboard', 302],
    'branch to platform' => ['branch1.superadmin@dev.com', 'branch', '/platform/dashboard', 302],
    'branch to driver' => ['branch1.superadmin@dev.com', 'branch', '/driver/dashboard', 302],
    'branch to customer' => ['branch1.superadmin@dev.com', 'branch', '/dashboard', 302],
    'driver to platform' => ['driver@dev.com', 'driver', '/platform/dashboard', 302],
    'driver to branch downtown' => ['driver@dev.com', 'driver', '/branch/downtown/dashboard', 302],
    'driver to customer' => ['driver@dev.com', 'driver', '/dashboard', 302],
    'customer to platform' => ['user@dev.com', 'customer', '/platform/dashboard', 302],
    'customer to branch downtown' => ['user@dev.com', 'customer', '/branch/downtown/dashboard', 302],
    'customer to driver' => ['user@dev.com', 'customer', '/driver/dashboard', 302],
]);

test('branch user receives forbidden when visiting another branch dashboard', function () {
    $user = User::query()->where('email', 'branch1.superadmin@dev.com')->firstOrFail();

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
    'platform' => ['superadmin@dev.com', 'platform'],
    'driver' => ['driver@dev.com', 'driver'],
    'customer' => ['user@dev.com', 'customer'],
]);

test('inactive portal user is logged out and redirected to login', function () {
    $user = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();
    $user->update(['is_active' => false]);

    $response = $this->actingAs($user, 'platform')->get('/platform/dashboard');

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('platform');
});
