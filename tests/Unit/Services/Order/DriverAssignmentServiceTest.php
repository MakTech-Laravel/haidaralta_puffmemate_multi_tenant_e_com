<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Services\Order\DriverAssignmentService;
use Illuminate\Validation\ValidationException;

test('a melbourne driver cannot be assigned to a sydney fulfillment', function () {
    $sydney = Branch::factory()->create();
    $melbourne = Branch::factory()->create();
    $actor = User::factory()->create(['user_type' => UserType::PLATFORM]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $melbourneDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $melbourneDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $melbourneDriver->id,
        'branch_id' => $melbourne->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $sydneyDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $sydneyDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $sydneyDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    scheduleDriverOnDuty($sydneyDriver, $sydney);

    $service = app(DriverAssignmentService::class);

    expect($service->candidates($fulfillment)->pluck('id')->all())
        ->toBe([$sydneyDriver->id]);

    expect(fn () => $service->assign($fulfillment, $melbourneDriver, $actor))
        ->toThrow(ValidationException::class);
});

test('an inactive or off branch driver is absent from the candidate list', function () {
    $sydney = Branch::factory()->create();
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $activeSydneyDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $activeSydneyDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $activeSydneyDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $inactiveDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => false,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $inactiveDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $inactiveDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $endedAssignmentDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $endedAssignmentDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $endedAssignmentDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
        'ended_at' => now()->subDay(),
    ]);

    scheduleDriverOnDuty($activeSydneyDriver, $sydney);

    $service = app(DriverAssignmentService::class);

    expect($service->candidates($fulfillment)->pluck('id')->all())
        ->toBe([$activeSydneyDriver->id]);
});

test('the available driver with the least workload is ranked first', function () {
    $sydney = Branch::factory()->create();
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $busyDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $busyDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $busyDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'driver_id' => $busyDriver->id,
        'status' => FulfillmentStatus::OutForDelivery,
    ]);

    $freeDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $freeDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $freeDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    scheduleDriverOnDuty($busyDriver, $sydney);
    scheduleDriverOnDuty($freeDriver, $sydney);

    $service = app(DriverAssignmentService::class);
    $candidates = $service->candidates($fulfillment)->pluck('id')->all();

    expect($candidates[0])->toBe($freeDriver->id)
        ->and($candidates)->toContain($busyDriver->id);
});

test('the closer available driver with equal workload is ranked first', function () {
    $sydney = Branch::factory()->create(['latitude' => -33.8688, 'longitude' => 151.2093]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $farDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->locatedAt(-37.8136, 144.9631)->create([
        'user_id' => $farDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $farDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $nearDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->locatedAt(-33.8650, 151.2094)->create([
        'user_id' => $nearDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $nearDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $missingCoordinatesDriver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $missingCoordinatesDriver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $missingCoordinatesDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    scheduleDriverOnDuty($farDriver, $sydney);
    scheduleDriverOnDuty($nearDriver, $sydney);
    scheduleDriverOnDuty($missingCoordinatesDriver, $sydney);

    $service = app(DriverAssignmentService::class);
    $candidates = $service->candidates($fulfillment)->pluck('id')->all();

    expect($candidates[0])->toBe($nearDriver->id)
        ->and($candidates[1])->toBe($farDriver->id)
        ->and($candidates)->toContain($missingCoordinatesDriver->id)
        ->and(array_search($missingCoordinatesDriver->id, $candidates, true))->toBeGreaterThan(
            array_search($farDriver->id, $candidates, true)
        );
});

test('an assigned driver cannot be replaced until the goods are returned', function () {
    $branch = Branch::factory()->create();
    $actor = User::factory()->create(['user_type' => UserType::PLATFORM]);
    $order = Order::factory()->create();
    $current = User::factory()->create(['user_type' => UserType::DRIVER, 'is_active' => true, 'branch_id' => null]);
    $next = User::factory()->create(['user_type' => UserType::DRIVER, 'is_active' => true, 'branch_id' => null]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $next->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($next, $branch);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $branch->id,
        'driver_id' => $current->id,
        'status' => FulfillmentStatus::Assigned,
    ]);

    expect(fn () => app(DriverAssignmentService::class)->assign($fulfillment, $next, $actor, 'Swap'))
        ->toThrow(ValidationException::class);

    $fulfillment->forceFill(['status' => FulfillmentStatus::Returned, 'driver_id' => null])->save();

    $result = app(DriverAssignmentService::class)->assign($fulfillment->refresh(), $next, $actor, 'Goods are back');

    expect($result->driver_id)->toBe($next->id)
        ->and($result->status)->toBe(FulfillmentStatus::Assigned);
});
