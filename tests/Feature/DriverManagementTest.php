<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverAvailabilityStatus;
use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\DriverRoster;
use App\Models\User;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);
});

test('platform can list and create drivers with branch assignments', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.drivers.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/drivers/index')
            ->has('table.data'));

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.store'), [
            'name' => 'Taylor Driver',
            'phone' => '+61420000001',
            'email' => 'taylor.driver@puffmemate.dev',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Car->value,
            'availability_status' => DriverAvailabilityStatus::Available->value,
            'branch_ids' => [$sydney->id, $melbourne->id],
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61420000001')->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and($driver->branch_id)->toBeNull()
        ->and($driver->driverProfile)->not->toBeNull()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->where('status', DriverAssignmentStatus::Active)->count())->toBe(2);
});

test('seeded driver has profile and multi branch assignments keyed by user id', function () {
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and(DriverProfile::query()->where('user_id', $driver->id)->exists())->toBeTrue()
        ->and(DriverBranchAssignment::query()->where('driver_id', $driver->id)->count())->toBe(2);
});

test('platform can add roster for actively assigned branch and rejects overlap', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $driver = User::query()->where('email', UserSeeder::DRIVER)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'date' => '2026-09-20',
            'start_time' => '09:00',
            'end_time' => '13:00',
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    expect(DriverRoster::query()->where('driver_id', $driver->id)->count())->toBe(1);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.drivers.show', $driver))
        ->post(route('platform.drivers.rosters.store', $driver), [
            'branch_id' => $sydney->id,
            'date' => '2026-09-20',
            'start_time' => '12:00',
            'end_time' => '16:00',
            'status' => 'scheduled',
        ])
        ->assertRedirect();

    expect(DriverRoster::query()->where('driver_id', $driver->id)->count())->toBe(1);
});

test('branch can create driver auto assigned to current branch', function () {
    $staff = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($staff, 'branch')
        ->get(route('branch.drivers.index', $sydney))
        ->assertOk();

    $this->actingAs($staff, 'branch')
        ->post(route('branch.drivers.store', ['branch' => $sydney->slug]), [
            'name' => 'Branch Driver',
            'phone' => '0412000099',
            'password' => 'password',
            'password_confirmation' => 'password',
            'vehicle_type' => DriverVehicleType::Bicycle->value,
            'is_active' => true,
        ])
        ->assertRedirect();

    $driver = User::query()->where('phone', '+61412000099')->first()
        ?? User::query()->where('phone', '0412000099')->firstOrFail();

    expect($driver->user_type)->toBe(UserType::DRIVER)
        ->and(DriverBranchAssignment::query()
            ->where('driver_id', $driver->id)
            ->where('branch_id', $sydney->id)
            ->where('status', DriverAssignmentStatus::Active)
            ->exists())->toBeTrue();
});

test('platform nested branch drivers index lists assigned drivers', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.branches.drivers.index', $sydney))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/branches/drivers/index')
            ->where('table.data', fn ($rows) => collect($rows)->contains(
                fn ($row) => $row['phone'] === UserSeeder::DRIVER_PHONE,
            )));
});
