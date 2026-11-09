<?php

use App\Enums\DriverAvailabilityStatus;
use App\Enums\FulfillmentStatus;
use App\Models\Branch;
use App\Models\User;
use App\Support\DriverAvailability;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $this->branch = Branch::query()->where('slug', 'sydney-cbd')->firstOrFail();
});

function setStoredAvailability(User $driver, DriverAvailabilityStatus $status): void
{
    $driver->driverProfile()->update(['availability_status' => $status]);
    $driver->unsetRelation('driverProfile');
}

test('a driver inside a shift window is tracked', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeTrue();
});

test('a driver with no shift and no delivery is not tracked', function () {
    expect(DriverAvailability::shouldTrack($this->driver))->toBeFalse();
});

test('a driver on break is not tracked even inside a shift', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    setStoredAvailability($this->driver, DriverAvailabilityStatus::OnBreak);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeFalse();
});

test('a busy driver inside a shift is still tracked', function () {
    scheduleDriverOnDuty($this->driver, $this->branch);
    setStoredAvailability($this->driver, DriverAvailabilityStatus::Busy);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeTrue();
});

test('a driver outside their shift is tracked while a delivery is still active', function () {
    driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeTrue();
});

test('a driver on break is not tracked even with an active delivery', function () {
    driverPortalOrder($this->driver, ['status' => FulfillmentStatus::OutForDelivery]);
    setStoredAvailability($this->driver, DriverAvailabilityStatus::OnBreak);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeFalse();
});

test('a finished delivery no longer keeps tracking on outside the shift', function () {
    driverPortalOrder($this->driver, ['status' => FulfillmentStatus::Delivered]);

    expect(DriverAvailability::shouldTrack($this->driver))->toBeFalse();
});

test('effective availability still works after extracting the shift check', function () {
    expect(DriverAvailability::effective($this->driver))->toBe(DriverAvailabilityStatus::Offline);

    scheduleDriverOnDuty($this->driver->fresh(), $this->branch);

    expect(DriverAvailability::effective($this->driver->fresh()))->toBe(DriverAvailabilityStatus::Available);
});
