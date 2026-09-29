<?php

use App\Enums\DriverAssignmentStatus;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverBranchAssignment;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Order\OrderOperationsReportService;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Inertia\Testing\AssertableInertia as Assert;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
});

test('sydney delivered sales are absent from melbourne branch reports', function () {
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'total_amount' => 80,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    StockTransfer::factory()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $order->fulfillment->id,
        'source_branch_id' => $melbourne->id,
        'destination_branch_id' => $sydney->id,
        'estimated_cost' => 17.5,
    ]);

    $reports = app(OrderOperationsReportService::class);

    expect($reports->branch($sydney)['sales'])->toBe(80.0)
        ->and($reports->branch($melbourne)['sales'])->toBe(0.0)
        ->and($reports->branch($melbourne)['outboundTransfers'])->toBe(1)
        ->and($reports->branch($melbourne)['outboundTransferCost'])->toBe(17.5)
        ->and($reports->platform()['transferCost'])->toBe(17.5);
});

test('a failed attempt increments the driver failure count and a cancellation does not', function () {
    $driver = User::factory()->create(['user_type' => UserType::DRIVER]);

    $delivered = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($delivered)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
    ]);

    $cancelled = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($cancelled)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Cancelled,
    ]);

    $reports = app(OrderOperationsReportService::class);

    expect($reports->driver($driver)['delivered'])->toBe(1)
        ->and($reports->driver($driver)['failedAttempts'])->toBe(0);
});

test('driver collections split by cod and pos', function () {
    $driver = User::factory()->create(['user_type' => UserType::DRIVER]);

    $codOrder = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($codOrder)->create(['driver_id' => $driver->id]);
    Payment::factory()->create([
        'order_id' => $codOrder->id,
        'gateway' => 'cod',
        'amount' => 40,
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
    ]);

    $posOrder = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($posOrder)->create(['driver_id' => $driver->id]);
    Payment::factory()->create([
        'order_id' => $posOrder->id,
        'gateway' => 'pos',
        'amount' => 25,
        'status' => PaymentStatus::Verified,
        'collected_by' => $driver->id,
    ]);

    $report = app(OrderOperationsReportService::class)->driver($driver);

    expect($report['collectedCod'])->toBe(40.0)
        ->and($report['collectedPos'])->toBe(25.0)
        ->and($report['collected'])->toBe(65.0);
});

test('platform admins can open the order operations report', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $driver = User::factory()->create(['user_type' => UserType::DRIVER]);
    $order = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($order)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'gateway' => 'cod',
        'amount' => 40,
        'status' => PaymentStatus::Collected,
        'collected_by' => $driver->id,
    ]);

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-operations'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reports/order-operations')
            ->has('report.orders')
            ->has('report.transferCost')
            ->has('drivers'));

    $drivers = collect($response->viewData('page')['props']['drivers']);
    $row = $drivers->firstWhere('id', $driver->id);

    expect($row)->not->toBeNull()
        ->and($row['delivered'])->toBe(1)
        ->and($row['collectedCod'])->toBe(40.0);
});

test('branch staff can open the branch order operations report with their drivers', function () {
    $admin = User::query()->where('email', UserSeeder::BRANCH_1_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $melbourne = Branch::query()->where('slug', BranchSeeder::MELBOURNE_COLLINS)->firstOrFail();

    $sydneyDriver = User::factory()->create(['user_type' => UserType::DRIVER]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $sydneyDriver->id,
        'branch_id' => $sydney->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $melbourneDriver = User::factory()->create(['user_type' => UserType::DRIVER]);
    DriverBranchAssignment::factory()->create([
        'driver_id' => $melbourneDriver->id,
        'branch_id' => $melbourne->id,
        'status' => DriverAssignmentStatus::Active,
    ]);

    $response = $this->actingAs($admin, 'branch')
        ->get(route('branch.reports.order-operations', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-operations')
            ->has('drivers'));

    $driverIds = collect($response->viewData('page')['props']['drivers'])->pluck('id');

    expect($driverIds)->toContain($sydneyDriver->id)
        ->and($driverIds)->not->toContain($melbourneDriver->id);
});
