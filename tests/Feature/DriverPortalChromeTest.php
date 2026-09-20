<?php

use App\Enums\DriverVehicleType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\DriverProfile;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open driver field tabs', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'route' => 'driver.route.index',
    'earnings' => 'driver.earnings.index',
    'activity' => 'driver.activity.index',
    'notifications' => 'driver.notifications.index',
    'profile' => 'driver.profile.index',
]);

test('drivers can open the field tab destinations', function (string $route, string $component) {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route($route))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component($component));
})->with([
    'route' => ['driver.route.index', 'driver/route/index'],
    'earnings' => ['driver.earnings.index', 'driver/earnings/index'],
    'activity' => ['driver.activity.index', 'driver/activity/index'],
    'notifications' => ['driver.notifications.index', 'driver/notifications/index'],
    'profile' => ['driver.profile.index', 'driver/profile/index'],
]);

test('drivers receive sequenced remaining and completed stops on the route tab', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.route.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/route/index')
            ->has('remaining')
            ->has('completed')
            ->missing('areas')
            ->missing('currentArea')
            ->has('nextStop.id')
            ->where('remaining.0.customerName', 'Elena Vargas')
            ->has('remaining.0.addressSummary')
            ->where('highlightId', null));
});

test('drivers can open the route tab with a highlighted drop', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.route.index', ['highlight' => 'drv-ord-1001']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/route/index')
            ->where('highlightId', 'drv-ord-1001')
            ->where('remaining.0.id', 'drv-ord-1001'));
});

test('drivers receive support contacts for the header account sheet', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('driverSupport.supportPhone', '+61418001100')
            ->where('driverSupport.branchPhone', '+61290001200'));
});

test('customers do not receive driver support contacts', function () {
    $customer = User::factory()->create();

    $this->actingAs($customer, 'customer')
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('driverSupport', null));
});

test('drivers can open profile settings from the account sheet destination', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('profile.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/profile')
            ->where('portal', 'driver'));
});

test('drivers receive identity vehicle and branch facts on the profile tab', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'branch_id' => null,
        'email_verified_at' => now(),
    ]);

    DriverProfile::factory()->available()->create([
        'user_id' => $driver->id,
        'license_number' => 'DL-TEST-001',
        'vehicle_type' => DriverVehicleType::Motorcycle,
    ]);

    $branch = Branch::factory()->create(['name' => 'Sydney CBD']);

    DriverBranchAssignment::factory()->create([
        'driver_id' => $driver->id,
        'branch_id' => $branch->id,
    ]);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.profile.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/profile/index')
            ->where('driver.name', $driver->name)
            ->where('driver.phone', $driver->phone)
            ->where('driver.license_number', 'DL-TEST-001')
            ->where('driver.vehicle_type_label', 'Motorcycle')
            ->where('driver.assignments.0.branch_name', 'Sydney CBD')
            ->missing('logout'));
});

test('drivers opening the old account url are sent to profile', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);

    $this->actingAs($driver, 'driver')
        ->get('/driver/account')
        ->assertRedirect(route('driver.profile.index'));
});
