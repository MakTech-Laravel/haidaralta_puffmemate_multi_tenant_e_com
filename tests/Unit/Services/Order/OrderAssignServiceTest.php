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
use App\Services\Order\OrderAssignService;

test('assign status payload reports not assign or assign with driver name', function () {
    $branch = Branch::factory()->create();
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'name' => 'List Driver',
        'branch_id' => null,
    ]);

    $open = OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'branch_id' => $branch->id,
        'driver_id' => null,
    ]);
    $taken = OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'branch_id' => $branch->id,
        'driver_id' => $driver->id,
    ]);
    $taken->setRelation('driver', $driver);

    $service = app(OrderAssignService::class);

    expect($service->assignStatusPayload($open))->toBe([
        'assign_status' => 'not_assign',
        'driver_id' => null,
        'driver_name' => null,
    ])->and($service->assignStatusPayload($taken))->toBe([
        'assign_status' => 'assign',
        'driver_id' => $driver->id,
        'driver_name' => 'List Driver',
    ]);
});

test('candidates payload maps ranked branch drivers for the show page', function () {
    $branch = Branch::factory()->create();
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Ready,
    ]);

    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'is_active' => true,
        'name' => 'Show Page Driver',
        'phone' => '+61411110000',
        'branch_id' => null,
    ]);
    DriverProfile::factory()->create([
        'user_id' => $driver->id,
        'availability_status' => DriverAvailabilityStatus::Available,
    ]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
        'status' => DriverAssignmentStatus::Active,
    ]);
    scheduleDriverOnDuty($driver, $branch);

    $payload = app(OrderAssignService::class)->candidatesPayload($fulfillment);

    expect($payload)->toHaveCount(1)
        ->and($payload[0])->toMatchArray([
            'id' => $driver->id,
            'name' => 'Show Page Driver',
            'phone' => '+61411110000',
        ])
        ->and($payload[0])->toHaveKey('avatar_url');
});

test('can assign only before pickup and again after a return', function () {
    $branch = Branch::factory()->create();
    $service = app(OrderAssignService::class);

    $pending = OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Pending,
    ]);
    $ready = OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Ready,
    ]);
    $delivered = OrderFulfillment::factory()->forOrder(Order::factory()->create())->create([
        'branch_id' => $branch->id,
        'status' => FulfillmentStatus::Delivered,
    ]);

    expect($service->canAssign($pending))->toBeFalse()
        ->and($service->canAssign($ready))->toBeTrue()
        ->and($service->canAssign($delivered))->toBeFalse();
});
