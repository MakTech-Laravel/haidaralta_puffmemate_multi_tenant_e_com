<?php

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Http;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->branch = Branch::query()->where('slug', 'sydney-cbd')->firstOrFail();

    // OSRM is the free routing option for OpenStreetMap; here it stands in for any routing provider.
    config(['maps.providers.osm.options.osrm_url' => 'https://osrm.test']);
});

function stopWithPin(User $driver, float $lat = -33.9000, float $lng = 151.2200): string
{
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::OutForDelivery], [
        'latitude' => $lat,
        'longitude' => $lng,
    ]);

    return $order->order_number;
}

function shareDriverPosition(mixed $test, User $driver): void
{
    $test->actingAs($driver, 'driver')
        ->postJson(route('driver.location.store'), ['latitude' => -33.8688, 'longitude' => 151.2093, 'accuracy' => 10])
        ->assertOk();
}

function osrmRoute(): array
{
    return ['routes' => [[
        'distance' => 5230.4,
        'duration' => 840.2,
        'geometry' => ['coordinates' => [[151.2093, -33.8688], [151.22, -33.9]]],
    ]]];
}

test('the route page sends each stop with its map pin', function () {
    $number = stopWithPin($this->driver, -33.9, 151.22);

    $this->actingAs($this->driver, 'driver')
        ->get(route('driver.route.index'))
        ->assertInertia(fn ($page) => $page
            ->where('remaining.0.orderNumber', $number)
            ->where('remaining.0.latitude', -33.9)
            ->where('remaining.0.longitude', 151.22));
});

test('a driver gets a road route from their position to a stop', function () {
    Http::fake(['osrm.test/*' => Http::response(osrmRoute())]);
    $number = stopWithPin($this->driver);
    shareDriverPosition($this, $this->driver);

    $this->actingAs($this->driver, 'driver')
        ->getJson(route('driver.route.directions', $number))
        ->assertOk()
        ->assertJsonPath('route.distance_meters', 5230)
        ->assertJsonPath('route.duration_seconds', 840)
        ->assertJsonPath('route.points.0', [-33.8688, 151.2093]);
});

test('repeating the same route request reuses the cached road route', function () {
    Http::fake(['osrm.test/*' => Http::response(osrmRoute())]);
    $number = stopWithPin($this->driver);
    shareDriverPosition($this, $this->driver);

    $this->actingAs($this->driver, 'driver')->getJson(route('driver.route.directions', $number))->assertOk();
    $this->actingAs($this->driver, 'driver')->getJson(route('driver.route.directions', $number))->assertOk();

    Http::assertSentCount(1);
});

test('a driver cannot request directions for another driver stop', function () {
    $number = stopWithPin($this->driver);
    $other = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();

    $this->actingAs($other, 'branch')
        ->getJson(route('driver.route.directions', $number))
        ->assertRedirect();

    $second = User::factory()->create(['user_type' => UserType::DRIVER, 'is_active' => true]);

    $this->actingAs($second, 'driver')
        ->getJson(route('driver.route.directions', $number))
        ->assertNotFound();
});

test('directions need a known position and a pinned stop', function () {
    Http::fake();
    $number = stopWithPin($this->driver);

    $this->actingAs($this->driver, 'driver')
        ->getJson(route('driver.route.directions', $number))
        ->assertStatus(409);

    shareDriverPosition($this, $this->driver);
    $unpinned = driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery], [
        'latitude' => null,
        'longitude' => null,
    ]);

    $this->actingAs($this->driver, 'driver')
        ->getJson(route('driver.route.directions', $unpinned->order_number))
        ->assertStatus(422);

    Http::assertNothingSent();
});

test('the driver is told when no routing provider is available', function () {
    config(['maps.providers.osm.options.osrm_url' => null]);
    $number = stopWithPin($this->driver);
    shareDriverPosition($this, $this->driver);

    $this->actingAs($this->driver, 'driver')
        ->getJson(route('driver.route.directions', $number))
        ->assertStatus(503)
        ->assertJsonPath('message', 'Road routing is not available right now.');
});
