<?php

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Permission;
use App\Models\User;
use App\Support\OpsDashboardAccess;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform super admin can open observability hub horizon and pulse', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    expect(OpsDashboardAccess::allowsHorizon($admin))->toBeTrue()
        ->and(OpsDashboardAccess::allowsPulse($admin))->toBeTrue()
        ->and(OpsDashboardAccess::allowsObservabilityHub($admin))->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.observability.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/observability/index')
            ->where('canViewHorizon', true)
            ->where('canViewPulse', true));

    $this->actingAs($admin, 'platform')
        ->get('/horizon')
        ->assertOk();

    $this->actingAs($admin, 'platform')
        ->get('/pulse')
        ->assertOk();
});

test('platform admin with view permissions can access matching dashboards', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_ADMIN)->firstOrFail();

    expect(OpsDashboardAccess::allowsHorizon($admin))->toBeTrue()
        ->and(OpsDashboardAccess::allowsPulse($admin))->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.observability.index'))
        ->assertOk();

    $this->actingAs($admin, 'platform')
        ->get('/horizon')
        ->assertOk();

    $this->actingAs($admin, 'platform')
        ->get('/pulse')
        ->assertOk();
});

test('platform staff without observability permissions is forbidden', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    expect(OpsDashboardAccess::allowsHorizon($user))->toBeFalse()
        ->and(OpsDashboardAccess::allowsPulse($user))->toBeFalse()
        ->and(OpsDashboardAccess::allowsObservabilityHub($user))->toBeFalse();

    $this->actingAs($user, 'platform')
        ->get(route('platform.observability.index'))
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get('/horizon')
        ->assertForbidden();

    $this->actingAs($user, 'platform')
        ->get('/pulse')
        ->assertForbidden();
});

test('platform user with only view-horizon sees hub but not pulse', function () {
    $user = User::factory()->create([
        'user_type' => UserType::PLATFORM,
        'email_verified_at' => now(),
        'is_active' => true,
    ]);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->givePermissionTo(
        Permission::findByName(PermissionEnum::VIEW_HORIZON->value, 'platform'),
    );

    expect(OpsDashboardAccess::allowsHorizon($user))->toBeTrue()
        ->and(OpsDashboardAccess::allowsPulse($user))->toBeFalse();

    $this->actingAs($user, 'platform')
        ->get(route('platform.observability.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('canViewHorizon', true)
            ->where('canViewPulse', false));

    $this->actingAs($user, 'platform')
        ->get('/horizon')
        ->assertOk();

    $this->actingAs($user, 'platform')
        ->get('/pulse')
        ->assertForbidden();
});

test('branch driver and customer cannot access ops dashboards', function () {
    $branch = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $customer = User::query()->where('email', UserSeeder::CUSTOMER)->firstOrFail();

    expect(OpsDashboardAccess::allowsHorizon($branch))->toBeFalse()
        ->and(OpsDashboardAccess::allowsPulse($branch))->toBeFalse()
        ->and(OpsDashboardAccess::allowsHorizon($driver))->toBeFalse()
        ->and(OpsDashboardAccess::allowsHorizon($customer))->toBeFalse();

    $this->actingAs($branch, 'branch')
        ->get(route('platform.observability.index'))
        ->assertRedirect(route('login'));

    $this->actingAs($branch, 'branch')
        ->get('/horizon')
        ->assertRedirect(route('login'));

    $this->actingAs($driver, 'driver')
        ->get('/pulse')
        ->assertRedirect(route('login'));

    $this->actingAs($customer, 'customer')
        ->get('/horizon')
        ->assertRedirect(route('login'));
});

test('guests are redirected to login for observability routes', function () {
    $this->get(route('platform.observability.index'))
        ->assertRedirect(route('login'));

    $this->get('/horizon')
        ->assertRedirect(route('login'));

    $this->get('/pulse')
        ->assertRedirect(route('login'));
});
