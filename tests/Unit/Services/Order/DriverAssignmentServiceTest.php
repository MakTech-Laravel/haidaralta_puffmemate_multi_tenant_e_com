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

    $service = app(DriverAssignmentService::class);

    expect($service->candidates($fulfillment)->pluck('id')->all())
        ->toBe([$sydneyDriver->id]);

    expect(fn () => $service->assign($fulfillment, $melbourneDriver, $actor))
        ->toThrow(ValidationException::class);
});
