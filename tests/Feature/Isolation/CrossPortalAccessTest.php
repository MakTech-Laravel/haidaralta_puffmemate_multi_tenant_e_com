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
    $response = $this->get($url);

    $response->assertRedirect(route('login'));
})->with([
    'platform' => ['/platform/dashboard'],
    'branch' => ['/branch/downtown/dashboard'],
    'driver' => ['/driver/dashboard'],
    'customer' => ['/dashboard'],
]);

test('platform user can access platform dashboard only', function () {
    $user = User::query()->where('email', 'platform@test.com')->firstOrFail();

    $this->actingAs($user, 'platform')
        ->get('/platform/dashboard')
        ->assertOk();

    $this->actingAs($user, 'platform')
        ->get('/branch/downtown/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'platform')
        ->get('/branch/uptown/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'platform')
        ->get('/driver/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'platform')
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});

test('branch user can access their branch dashboard only', function () {
    $user = User::query()->where('email', 'branch@test.com')->firstOrFail();

    $this->actingAs($user, 'branch')
        ->get('/branch/downtown/dashboard')
        ->assertOk();

    $this->actingAs($user, 'branch')
        ->get('/branch/uptown/dashboard')
        ->assertForbidden();

    $this->actingAs($user, 'branch')
        ->get('/platform/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'branch')
        ->get('/driver/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'branch')
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});

test('driver user can access driver dashboard only', function () {
    $user = User::query()->where('email', 'driver@test.com')->firstOrFail();

    $this->actingAs($user, 'driver')
        ->get('/driver/dashboard')
        ->assertOk();

    $this->actingAs($user, 'driver')
        ->get('/platform/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'driver')
        ->get('/branch/downtown/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'driver')
        ->get('/dashboard')
        ->assertRedirect(route('login'));
});

test('customer user can access customer dashboard only', function () {
    $user = User::query()->where('email', 'customer@test.com')->firstOrFail();

    $this->actingAs($user, 'customer')
        ->get('/dashboard')
        ->assertOk();

    $this->actingAs($user, 'customer')
        ->get('/platform/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'customer')
        ->get('/branch/downtown/dashboard')
        ->assertRedirect(route('login'));

    $this->actingAs($user, 'customer')
        ->get('/driver/dashboard')
        ->assertRedirect(route('login'));
});

test('inactive portal user is logged out and redirected to login', function () {
    $user = User::query()->where('email', 'platform@test.com')->firstOrFail();
    $user->update(['is_active' => false]);

    $response = $this->actingAs($user, 'platform')->get('/platform/dashboard');

    $response
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('email');

    $this->assertGuest('platform');
});
