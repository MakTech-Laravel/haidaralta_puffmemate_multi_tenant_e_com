<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Events\DriverLocationUpdated;
use App\Models\Branch;
use App\Models\DriverLocation;
use App\Models\User;
use App\Services\Driver\DriverLocationService;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Event;
use Illuminate\Testing\TestResponse;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->branch = Branch::query()->where('slug', 'sydney-cbd')->firstOrFail();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function pingLocation(mixed $test, User $driver, array $overrides = []): TestResponse
{
    return $test->actingAs($driver, 'driver')->postJson(route('driver.location.store'), array_merge([
        'latitude' => -33.8688,
        'longitude' => 151.2093,
        'accuracy' => 10,
    ], $overrides));
}

test('a reading on duty is accepted, cached and broadcast to the branch and platform', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver, ['speed' => 4.5, 'heading' => 90])
        ->assertOk()
        ->assertJson(['tracking' => true, 'accepted' => true, 'reason' => null]);

    $last = app(DriverLocationService::class)->lastPosition($this->driver->id);

    expect($last['lat'])->toBe(-33.8688)->and($last['heading'])->toBe(90);

    Event::assertDispatched(DriverLocationUpdated::class, function (DriverLocationUpdated $event): bool {
        $channels = array_map(fn ($channel) => $channel->name, $event->broadcastOn());

        return $event->driverId === $this->driver->id
            && $event->branchIds === [$this->branch->id]
            && $channels === ['private-platform.drivers.locations', 'private-branch.'.$this->branch->id.'.drivers'];
    });
});

test('an idle driver on duty is cached but writes no breadcrumb', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver)->assertOk();

    expect(DriverLocation::query()->count())->toBe(0)
        ->and(app(DriverLocationService::class)->lastPosition($this->driver->id))->not->toBeNull();
});

test('off-duty readings are rejected without storing or broadcasting anything', function () {
    Event::fake([DriverLocationUpdated::class]);

    pingLocation($this, $this->driver)->assertNoContent();

    expect(app(DriverLocationService::class)->lastPosition($this->driver->id))->toBeNull()
        ->and(DriverLocation::query()->count())->toBe(0);
    Event::assertNotDispatched(DriverLocationUpdated::class);
});

test('a driver on break is told to stop sharing', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);
    $this->driver->driverProfile()->update(['availability_status' => DriverAvailabilityStatus::OnBreak]);

    pingLocation($this, $this->driver->fresh())->assertNoContent();

    Event::assertNotDispatched(DriverLocationUpdated::class);
});

test('an active delivery outside the shift is tracked and leaves a breadcrumb linked to it', function () {
    Event::fake([DriverLocationUpdated::class]);
    $order = driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);
    $fulfillment = $order->fulfillments()->firstOrFail();

    pingLocation($this, $this->driver)->assertOk()->assertJson(['accepted' => true]);

    $crumb = DriverLocation::query()->firstOrFail();

    expect($crumb->fulfillment_id)->toBe($fulfillment->id)
        ->and($crumb->driver_id)->toBe($this->driver->id);
    Event::assertDispatched(DriverLocationUpdated::class, fn (DriverLocationUpdated $event) => $event->fulfillmentId === $fulfillment->id);
});

test('tiny movements update the position but are not broadcast again', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver)->assertOk();
    $this->travel(5)->seconds();
    pingLocation($this, $this->driver, ['latitude' => -33.86881])->assertOk()->assertJson(['accepted' => true]);

    Event::assertDispatchedTimes(DriverLocationUpdated::class, 1);
});

test('a real movement is broadcast and sampled as a breadcrumb during a delivery', function () {
    Event::fake([DriverLocationUpdated::class]);
    driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);

    pingLocation($this, $this->driver)->assertOk();
    $this->travel(10)->seconds();
    pingLocation($this, $this->driver, ['latitude' => -33.8700])->assertOk();

    Event::assertDispatchedTimes(DriverLocationUpdated::class, 2);
    expect(DriverLocation::query()->count())->toBe(2);
});

test('a stationary driver still sends a heartbeat broadcast after a minute', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver)->assertOk();
    $this->travel(61)->seconds();
    pingLocation($this, $this->driver)->assertOk();

    Event::assertDispatchedTimes(DriverLocationUpdated::class, 2);
});

test('inaccurate readings are ignored', function () {
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver, ['accuracy' => 500])
        ->assertOk()
        ->assertJson(['accepted' => false, 'reason' => 'inaccurate']);

    expect(app(DriverLocationService::class)->lastPosition($this->driver->id))->toBeNull();
});

test('stale device readings are ignored', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver, ['recorded_at' => now()->subMinutes(10)->toIso8601String()])
        ->assertOk()
        ->assertJson(['accepted' => false, 'reason' => 'too_old']);
});

test('a reading older than the stored position is ignored', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver)->assertOk()->assertJson(['accepted' => true]);

    pingLocation($this, $this->driver, ['recorded_at' => now()->subSeconds(30)->toIso8601String()])
        ->assertOk()
        ->assertJson(['accepted' => false, 'reason' => 'out_of_order']);
});

test('an impossible jump is ignored', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver)->assertOk();
    $this->travel(10)->seconds();

    pingLocation($this, $this->driver, ['latitude' => -37.8136, 'longitude' => 144.9631])
        ->assertOk()
        ->assertJson(['accepted' => false, 'reason' => 'jump']);

    expect(app(DriverLocationService::class)->lastPosition($this->driver->id)['lat'])->toBe(-33.8688);
});

test('a phone clock in the future is replaced with the server time', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver, ['recorded_at' => now()->addDay()->toIso8601String()])
        ->assertOk()
        ->assertJson(['accepted' => true]);
});

test('invalid coordinates are rejected', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    pingLocation($this, $this->driver, ['latitude' => 95, 'heading' => 400])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['latitude', 'heading']);
});

test('only a signed-in driver can report a location', function () {
    $this->postJson(route('driver.location.store'), ['latitude' => 1, 'longitude' => 1])
        ->assertRedirect();
});

test('location reports are throttled per driver', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    foreach (range(1, 30) as $ignored) {
        pingLocation($this, $this->driver, ['accuracy' => 500])->assertOk();
    }

    pingLocation($this, $this->driver, ['accuracy' => 500])->assertStatus(429);
});

test('the prune command removes only breadcrumbs older than the retention period', function () {
    $old = DriverLocation::factory()->create(['driver_id' => $this->driver->id, 'recorded_at' => now()->subDays(31)]);
    $recent = DriverLocation::factory()->create(['driver_id' => $this->driver->id, 'recorded_at' => now()->subDays(29)]);

    $this->artisan('driver-locations:prune')->assertSuccessful();

    expect(DriverLocation::query()->whereKey($old->id)->exists())->toBeFalse()
        ->and(DriverLocation::query()->whereKey($recent->id)->exists())->toBeTrue();
});

function useReverbForDriverLocationAuth(): void
{
    config([
        'broadcasting.default' => 'reverb',
        'broadcasting.connections.reverb.key' => 'test-key',
        'broadcasting.connections.reverb.secret' => 'test-secret',
        'broadcasting.connections.reverb.app_id' => 'test-id',
        'broadcasting.connections.reverb.options.host' => 'localhost',
        'broadcasting.connections.reverb.options.port' => 443,
        'broadcasting.connections.reverb.options.scheme' => 'https',
        'broadcasting.connections.reverb.options.useTLS' => true,
    ]);

    require base_path('routes/channels.php');
}

test('only platform staff with driver access can listen to all drivers', function () {
    useReverbForDriverLocationAuth();

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $outsider = User::factory()->create(['user_type' => UserType::PLATFORM, 'email_verified_at' => now()]);
    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $outsider->assignRole(RoleEnum::STAFF->value);
    app(PermissionRegistrar::class)->setPermissionsTeamId(null);

    $auth = ['socket_id' => '1234.5678', 'channel_name' => 'private-platform.drivers.locations'];

    $this->actingAs($admin, 'platform')->postJson('/broadcasting/auth', $auth)->assertOk();
    $this->actingAs($outsider, 'platform')->postJson('/broadcasting/auth', $auth)->assertForbidden();
    $this->actingAs($this->driver, 'driver')->postJson('/broadcasting/auth', $auth)->assertForbidden();
});

test('a branch can only listen to its own drivers', function () {
    useReverbForDriverLocationAuth();

    $manager = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $otherBranch = Branch::query()->where('slug', 'melbourne-collins')->firstOrFail();

    $this->actingAs($manager, 'branch')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-branch.'.$this->branch->id.'.drivers',
        ])
        ->assertOk();

    $this->actingAs($manager, 'branch')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-branch.'.$otherBranch->id.'.drivers',
        ])
        ->assertForbidden();
});

test('a driver cannot listen to a branch live map', function () {
    useReverbForDriverLocationAuth();

    $this->actingAs($this->driver, 'driver')
        ->postJson('/broadcasting/auth', [
            'socket_id' => '1234.5678',
            'channel_name' => 'private-branch.'.$this->branch->id.'.drivers',
        ])
        ->assertForbidden();
});
