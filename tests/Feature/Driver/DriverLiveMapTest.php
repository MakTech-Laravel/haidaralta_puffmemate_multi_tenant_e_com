<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Events\DriverLocationUpdated;
use App\Models\Branch;
use App\Models\User;
use App\Services\Driver\DriverMapWatchers;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\Event;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->branch = Branch::query()->where('slug', 'sydney-cbd')->firstOrFail();
    $this->otherBranch = Branch::query()->where('slug', 'melbourne-collins')->firstOrFail();
    $this->manager = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $this->otherManager = User::query()->where('email', UserSeeder::BRANCH_2_SUPER_ADMIN)->firstOrFail();
    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
});

/**
 * @param  array<string, mixed>  $overrides
 */
function driverPing(mixed $test, User $driver, array $overrides = []): void
{
    $test->actingAs($driver, 'driver')
        ->postJson(route('driver.location.store'), array_merge([
            'latitude' => -33.8688,
            'longitude' => 151.2093,
            'accuracy' => 10,
        ], $overrides))
        ->assertOk();
}

test('a branch sees its on-duty drivers with their last position', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    driverPing($this, $this->driver);

    $this->actingAs($this->manager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('branch/drivers/live')
            ->has('board.drivers', 1)
            ->where('board.drivers.0.id', $this->driver->id)
            ->where('board.drivers.0.signal', 'live')
            ->where('board.drivers.0.position.lat', -33.8688)
            ->where('channels', ['branch.'.$this->branch->id.'.drivers'])
            ->where('watchUrl', '/branch/'.$this->branch->slug.'/driver-map/watch'));
});

test('a branch never sees another branch driver', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    driverPing($this, $this->driver);

    $this->actingAs($this->otherManager, 'branch')
        ->get(route('branch.drivers.live', $this->otherBranch))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->has('board.drivers', 0));

    $this->actingAs($this->otherManager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertForbidden();
});

test('drivers off duty or on break are not on the map', function () {
    $this->actingAs($this->manager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertInertia(fn ($page) => $page->has('board.drivers', 0)->where('board.off_duty_count', fn ($count) => $count >= 1));

    scheduleDriverOnDuty($this->driver, $this->branch);
    $this->driver->driverProfile()->update(['availability_status' => DriverAvailabilityStatus::OnBreak]);

    $this->actingAs($this->manager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertInertia(fn ($page) => $page->has('board.drivers', 0));
});

test('an on-duty driver who has not sent a position yet is listed without a signal', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    $this->actingAs($this->manager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertInertia(fn ($page) => $page
            ->where('board.drivers.0.signal', 'none')
            ->where('board.drivers.0.position', null));
});

test('a driver with an active delivery shows the order and an old position turns stale', function () {
    $order = driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);
    driverPing($this, $this->driver);
    $this->travel(3)->minutes();

    $this->actingAs($this->manager, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertInertia(fn ($page) => $page
            ->where('board.drivers.0.signal', 'stale')
            ->where('board.drivers.0.order.number', $order->order_number)
            ->where('board.drivers.0.order.status', 'out_for_delivery'));
});

test('the platform sees drivers from every branch it scopes', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    driverPing($this, $this->driver);

    $this->actingAs($this->admin, 'platform')
        ->get(route('platform.drivers.live'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/live')
            ->has('board.drivers', 1)
            ->where('board.branches.0.name', $this->branch->name)
            ->where('channels', ['platform.drivers.locations'])
            ->where('watchUrl', '/platform/driver-map/watch'));
});

test('staff without driver permissions cannot open the live maps', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_STAFF)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.live', $this->branch))
        ->assertForbidden();
});

test('opening a map marks that scope as watched', function () {
    $watchers = app(DriverMapWatchers::class);

    expect($watchers->isWatched([$this->branch->id]))->toBeFalse();

    $this->actingAs($this->manager, 'branch')
        ->postJson(route('branch.drivers.live.watch', $this->branch))
        ->assertNoContent();

    expect($watchers->isWatched([$this->branch->id]))->toBeTrue()
        ->and($watchers->isWatched([$this->otherBranch->id]))->toBeFalse();
});

test('opening the platform map marks every branch as watched', function () {
    $watchers = app(DriverMapWatchers::class);

    $this->actingAs($this->admin, 'platform')
        ->postJson(route('platform.drivers.live.watch'))
        ->assertNoContent();

    expect($watchers->isWatched([$this->otherBranch->id]))->toBeTrue();
});

test('positions are only pushed over Reverb while a map is open', function () {
    config(['drivers.tracking.broadcast_only_when_watched' => true]);
    Event::fake([DriverLocationUpdated::class]);
    scheduleDriverOnDuty($this->driver, $this->branch);

    driverPing($this, $this->driver);
    Event::assertNotDispatched(DriverLocationUpdated::class);

    app(DriverMapWatchers::class)->touchBranch($this->branch->id);
    $this->travel(5)->seconds();
    driverPing($this, $this->driver);

    Event::assertDispatchedTimes(DriverLocationUpdated::class, 1);
});
