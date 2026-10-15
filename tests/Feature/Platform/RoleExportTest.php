<?php

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

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('platform roles export returns csv', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.roles.export', [
            'format' => 'csv',
            'scope' => 'all',
        ]));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});

test('platform permissions export returns csv', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.permissions.export'));

    $response->assertOk();
    expect($response->headers->get('content-disposition'))->toContain('.csv');
});
