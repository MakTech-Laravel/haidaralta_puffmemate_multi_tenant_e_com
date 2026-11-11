<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Driver\DriverLocationService;
use App\Services\Order\BranchOrderNotifier;
use App\Services\Order\DriverAssignmentService;
use App\Services\Order\DriverAutoAssignService;
use App\Services\Order\FulfillmentStatusService;
use Illuminate\Support\Facades\Cache;

const AUTO_ASSIGN_CUSTOMER_LAT = -33.8688;
const AUTO_ASSIGN_CUSTOMER_LNG = 151.2093;

function autoAssignDriver(Branch $branch, ?array $position = null, DriverAvailabilityStatus $availability = DriverAvailabilityStatus::Available): User
{
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'availability_status' => $availability,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $branch);

    if ($position !== null) {
        Cache::put(DriverLocationService::cacheKey($driver->id), [
            'lat' => $position[0],
            'lng' => $position[1],
            'coarse' => false,
            'accuracy' => null,
            'speed' => null,
            'heading' => null,
            'ts' => time(),
            'fulfillment_id' => null,
            'branch_ids' => [$branch->id],
        ], 600);
    }

    return $driver;
}

/**
 * @param  array<string, mixed>  $fulfillment
 * @param  array<string, mixed>  $order
 */
function autoAssignFulfillment(Branch $branch, FulfillmentStatus $status = FulfillmentStatus::Placed, array $fulfillment = [], array $order = []): OrderFulfillment
{
    $orderModel = Order::factory()->create(array_merge([
        'source' => OrderSource::CustomerCheckout,
        'latitude' => AUTO_ASSIGN_CUSTOMER_LAT,
        'longitude' => AUTO_ASSIGN_CUSTOMER_LNG,
    ], $order));

    return OrderFulfillment::factory()->forOrder($orderModel)->create(array_merge([
        'branch_id' => $branch->id,
        'status' => $status,
    ], $fulfillment));
}

function expectBranchNotified(int $times): void
{
    $notifier = Mockery::mock(BranchOrderNotifier::class);
    $notifier->shouldReceive('newOrder')->times($times);
    app()->instance(BranchOrderNotifier::class, $notifier);
}

test('reserve picks the driver with the fewest open orders and leaves the order untouched', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $busy = autoAssignDriver($branch, [AUTO_ASSIGN_CUSTOMER_LAT, AUTO_ASSIGN_CUSTOMER_LNG]);
    $free = autoAssignDriver($branch, [-33.95, 151.3]);
    autoAssignFulfillment($branch, FulfillmentStatus::OutForDelivery, ['driver_id' => $busy->id]);
    $fulfillment = autoAssignFulfillment($branch);

    $picked = app(DriverAutoAssignService::class)->reserve($fulfillment);

    expect($picked->id)->toBe($free->id)
        ->and($fulfillment->refresh()->reserved_driver_id)->toBe($free->id)
        ->and($fulfillment->driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Placed);
});

test('reserved orders count as workload for the next pick', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $first = autoAssignDriver($branch, [AUTO_ASSIGN_CUSTOMER_LAT, AUTO_ASSIGN_CUSTOMER_LNG]);
    $second = autoAssignDriver($branch, [-33.95, 151.3]);
    autoAssignFulfillment($branch, FulfillmentStatus::Processing, ['reserved_driver_id' => $first->id]);
    $fulfillment = autoAssignFulfillment($branch);

    $picked = app(DriverAutoAssignService::class)->reserve($fulfillment);

    expect($picked->id)->toBe($second->id);
});

test('ties go to the driver closest to the customer by live location', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $far = autoAssignDriver($branch, [-34.5, 150.9]);
    $near = autoAssignDriver($branch, [-33.87, 151.21]);
    $fulfillment = autoAssignFulfillment($branch);

    $picked = app(DriverAutoAssignService::class)->reserve($fulfillment);

    expect($far->id)->toBeLessThan($near->id)
        ->and($picked->id)->toBe($near->id);
});

test('a driver without a live position sorts after one with a position but is not excluded', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $unknown = autoAssignDriver($branch);
    $known = autoAssignDriver($branch, [-34.5, 150.9]);
    $service = app(DriverAutoAssignService::class);

    expect($service->reserve(autoAssignFulfillment($branch))->id)->toBe($known->id);

    $onlyUnknown = Branch::factory()->create();
    $lonely = autoAssignDriver($onlyUnknown);

    expect($service->reserve(autoAssignFulfillment($onlyUnknown))->id)->toBe($lonely->id)
        ->and($unknown->id)->not->toBe($lonely->id);
});

test('off duty drivers and drivers of other branches are never picked', function () {
    expectBranchNotified(1);
    $branch = Branch::factory()->create();
    autoAssignDriver($branch, null, DriverAvailabilityStatus::OnBreak);
    autoAssignDriver(Branch::factory()->create());
    $fulfillment = autoAssignFulfillment($branch);

    expect(app(DriverAutoAssignService::class)->reserve($fulfillment))->toBeNull()
        ->and($fulfillment->refresh()->reserved_driver_id)->toBeNull();
});

test('reserve is idempotent and only runs for placed customer checkout orders', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $driver = autoAssignDriver($branch);
    $other = autoAssignDriver($branch);
    $service = app(DriverAutoAssignService::class);

    $fulfillment = autoAssignFulfillment($branch);
    $service->reserve($fulfillment);
    $service->reserve($fulfillment);
    $firstReserved = $fulfillment->refresh()->reserved_driver_id;

    $pending = autoAssignFulfillment($branch, FulfillmentStatus::Pending);
    $driverSale = autoAssignFulfillment($branch, FulfillmentStatus::Placed, [], ['source' => OrderSource::DriverSale]);
    $wholesale = autoAssignFulfillment($branch, FulfillmentStatus::Placed, [], ['source' => OrderSource::Wholesale]);

    expect($firstReserved)->toBeIn([$driver->id, $other->id])
        ->and($service->reserve($pending))->toBeNull()
        ->and($service->reserve($driverSale))->toBeNull()
        ->and($service->reserve($wholesale))->toBeNull()
        ->and($pending->refresh()->reserved_driver_id)->toBeNull()
        ->and($driverSale->refresh()->reserved_driver_id)->toBeNull()
        ->and($wholesale->refresh()->reserved_driver_id)->toBeNull();
});

test('promoteAtReady assigns the reserved driver through the normal assignment flow', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $driver = autoAssignDriver($branch);
    $actor = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $branch->id]);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Ready, ['reserved_driver_id' => $driver->id]);

    app(DriverAutoAssignService::class)->promoteAtReady($fulfillment, $actor);

    $fulfillment->refresh();

    expect($fulfillment->driver_id)->toBe($driver->id)
        ->and($fulfillment->reserved_driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Assigned)
        ->and($fulfillment->driverAssignmentHistories()->count())->toBe(1)
        ->and($fulfillment->driverAssignmentHistories()->first()->reason)->toBe(DriverAutoAssignService::PROMOTION_REASON);
});

test('promoteAtReady picks another driver when the reserved one is no longer available', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $reserved = autoAssignDriver($branch, null, DriverAvailabilityStatus::OnBreak);
    $replacement = autoAssignDriver($branch);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Ready, ['reserved_driver_id' => $reserved->id]);

    app(DriverAutoAssignService::class)->promoteAtReady($fulfillment);

    expect($fulfillment->refresh()->driver_id)->toBe($replacement->id)
        ->and($fulfillment->reserved_driver_id)->toBeNull();
});

test('promoteAtReady leaves a ready order for manual assignment and notifies the branch when nobody is available', function () {
    expectBranchNotified(1);
    $branch = Branch::factory()->create();
    $reserved = autoAssignDriver($branch, null, DriverAvailabilityStatus::OnBreak);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Ready, ['reserved_driver_id' => $reserved->id]);

    app(DriverAutoAssignService::class)->promoteAtReady($fulfillment);

    expect($fulfillment->refresh()->driver_id)->toBeNull()
        ->and($fulfillment->reserved_driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Ready);
});

test('confirming a pending order reserves a driver and marking it ready assigns them', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $driver = autoAssignDriver($branch);
    $staff = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $branch->id]);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Pending);
    $statuses = app(FulfillmentStatusService::class);

    $statuses->transition($fulfillment, FulfillmentStatus::Placed, $staff);

    expect($fulfillment->refresh()->reserved_driver_id)->toBe($driver->id)
        ->and($fulfillment->driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Placed);

    $statuses->transition($fulfillment, FulfillmentStatus::Processing, $staff);
    $statuses->markReady($fulfillment->refresh(), $staff);

    expect($fulfillment->refresh()->driver_id)->toBe($driver->id)
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Assigned);
});

test('cancelling an order clears the driver reservation', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $driver = autoAssignDriver($branch);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Placed, ['reserved_driver_id' => $driver->id]);

    app(FulfillmentStatusService::class)->cancel($fulfillment, null, 'Test cancel', true);

    expect($fulfillment->refresh()->reserved_driver_id)->toBeNull()
        ->and($fulfillment->status)->toBe(FulfillmentStatus::Cancelled);
});

test('a manual assignment replaces the reservation so the reserved driver is not counted as busy', function () {
    expectBranchNotified(0);
    $branch = Branch::factory()->create();
    $reserved = autoAssignDriver($branch);
    $manual = autoAssignDriver($branch);
    $actor = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $branch->id]);
    $fulfillment = autoAssignFulfillment($branch, FulfillmentStatus::Placed, ['reserved_driver_id' => $reserved->id]);

    app(DriverAssignmentService::class)->assign($fulfillment, $manual, $actor);

    expect($fulfillment->refresh()->driver_id)->toBe($manual->id)
        ->and($fulfillment->reserved_driver_id)->toBeNull()
        ->and(app(DriverAssignmentService::class)->workload()->get($reserved->id))->toBeNull();
});
