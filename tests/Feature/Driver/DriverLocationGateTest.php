<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    config(['drivers.tracking.gate_enabled' => true]);

    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->branch = Branch::query()->where('slug', 'sydney-cbd')->firstOrFail();
});

function sendFirstPing(mixed $test, User $driver): void
{
    $test->actingAs($driver, 'driver')
        ->postJson(route('driver.location.store'), ['latitude' => -33.8688, 'longitude' => 151.2093, 'accuracy' => 10])
        ->assertOk();
}

test('the portal is not gated while the gate is disabled', function () {
    config(['drivers.tracking.gate_enabled' => false]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    $this->actingAs($this->driver, 'driver')->get(route('driver.dashboard'))->assertOk();
});

test('a driver on duty without a position is sent to the location prompt and returns afterwards', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.orders.index'))
        ->assertRedirect(route('driver.location.required'));

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.location.required'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('driver/location-required')
            ->where('intended', route('driver.orders.index')));

    sendFirstPing($this, $this->driver);

    $this->actingAs($this->driver, 'driver')->get(route('driver.orders.index'))->assertOk();
});

test('a driver off duty is never gated', function () {
    $this->actingAs($this->driver, 'driver')->get(route('driver.dashboard'))->assertOk();
});

test('a driver on break is not gated even inside a shift', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    $this->driver->driverProfile()->update(['availability_status' => DriverAvailabilityStatus::OnBreak]);

    $this->actingAs($this->driver->fresh(), 'driver')->get(route('driver.dashboard'))->assertOk();
});

test('a driver carrying an active delivery outside the shift is gated', function () {
    driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertRedirect(route('driver.location.required'));
});

test('the gate returns once the position goes stale', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    sendFirstPing($this, $this->driver);

    $this->actingAs($this->driver, 'driver')->get(route('driver.dashboard'))->assertOk();

    $this->travel(3)->minutes();

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertRedirect(route('driver.location.required'));
});

test('the location endpoint stays reachable while the driver is gated', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    sendFirstPing($this, $this->driver);
});

test('background requests from a gated driver get a locked response instead of a redirect', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    $this->actingAs($this->driver, 'driver')
        ->getJson(route('driver.notifications.index'))
        ->assertStatus(423)
        ->assertJsonPath('gate_url', route('driver.location.required'));
});

test('the prompt page sends a driver who is not gated back to the portal', function () {
    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.location.required'))
        ->assertRedirect(route('driver.dashboard'));
});

test('the tracker settings are shared with driver pages only', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    sendFirstPing($this, $this->driver);

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertInertia(fn ($page) => $page
            ->where('driverTracking.should_share', true)
            ->where('driverTracking.location_url', '/driver/location')
            ->where('driverTracking.gate_url', '/driver/location-required')
            ->where('driverTracking.heartbeat_seconds', 45));

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.settings.map-provider.edit'))
        ->assertInertia(fn ($page) => $page->where('driverTracking', null));
});

test('an off-duty driver is told not to share', function () {
    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertInertia(fn ($page) => $page->where('driverTracking.should_share', false));
});
