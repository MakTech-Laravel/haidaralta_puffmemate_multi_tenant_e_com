<?php

use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('non platform sessions cannot access platform admin routes', function (
    string $email,
    string $guard,
) {
    $user = User::query()->where('email', $email)->firstOrFail();

    $this->actingAs($user, $guard)
        ->get(route('platform.roles.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($user, $guard)
        ->get(route('platform.permissions.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($user, $guard)
        ->get(route('platform.branch-staff.index'))
        ->assertRedirect(route('login'));
})->with([
    'branch' => ['branch1.superadmin@dev.com', 'branch'],
    'driver' => ['driver@dev.com', 'driver'],
    'customer' => ['user@dev.com', 'customer'],
]);

test('platform session cannot access branch staff routes', function () {
    $platform = User::query()->where('email', 'superadmin@dev.com')->firstOrFail();
    $downtown = Branch::query()->where('slug', 'downtown')->firstOrFail();

    $this->actingAs($platform, 'platform')
        ->get(route('branch.staff.index', $downtown))
        ->assertRedirect(route('login'));
});
