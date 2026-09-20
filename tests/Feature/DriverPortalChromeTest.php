<?php

use App\Enums\UserType;
use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot open driver field tabs', function (string $route) {
    $this->get(route($route))->assertRedirect(route('login'));
})->with([
    'route' => 'driver.route.index',
    'activity' => 'driver.activity.index',
    'notifications' => 'driver.notifications.index',
    'account' => 'driver.account.index',
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
    'activity' => ['driver.activity.index', 'driver/activity/index'],
    'notifications' => ['driver.notifications.index', 'driver/notifications/index'],
    'account' => ['driver.account.index', 'driver/account/index'],
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
            ->has('areas')
            ->has('nextStop.id')
            ->where('areas.0.label', 'Surry Hills')
            ->where('areas.1.label', 'Darlinghurst')
            ->where('currentArea.label', 'Surry Hills')
            ->where('remaining.0.areaLabel', 'Surry Hills')
            ->has('remaining.0.customerName')
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
