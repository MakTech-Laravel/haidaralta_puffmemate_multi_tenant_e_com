<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
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

test('platform admins can open the order operations report', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-operations'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reports/order-operations')
            ->has('report.orders')
            ->has('report.transferCost'));
});
