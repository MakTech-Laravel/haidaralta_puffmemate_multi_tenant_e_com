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
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\StockTransfer;
use App\Models\User;
use App\Services\Order\OrderReportService;
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

    $reports = app(OrderReportService::class);

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

    $reports = app(OrderReportService::class);

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

    $report = app(OrderReportService::class)->driver($driver);

    expect($report['collectedCod'])->toBe(40.0)
        ->and($report['collectedPos'])->toBe(25.0)
        ->and($report['collected'])->toBe(65.0);
});

test('platform admins can open the order reports page', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $driver = User::factory()->create(['user_type' => UserType::DRIVER]);
    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'total_amount' => 40,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
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

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/reports/order-reports')
            ->where('dashboard.month', now()->format('Y-m'))
            ->where('dashboard.summary.orders', 1)
            ->where('dashboard.summary.delivered', 1)
            ->where('dashboard.summary.cod_collected', 40)
            ->has('dashboard.products')
            ->has('dashboard.branches')
            ->has('dashboard.drivers'));
});

test('order reports dashboard respects the selected month', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $recent = Order::factory()->create(['status' => OrderStatus::Delivered, 'total_amount' => 50]);
    OrderFulfillment::factory()->forOrder($recent)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Delivered,
    ]);

    $old = Order::factory()->create(['status' => OrderStatus::Delivered, 'total_amount' => 80]);
    $oldFulfillment = OrderFulfillment::factory()->forOrder($old)->create([
        'branch_id' => $sydney->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    $old->forceFill(['created_at' => now()->subMonthsNoOverflow(2)])->saveQuietly();
    $oldFulfillment->forceFill(['created_at' => now()->subMonthsNoOverflow(2)])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['month' => now()->subMonthsNoOverflow(2)->format('Y-m')],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.month', now()->subMonthsNoOverflow(2)->format('Y-m'))
            ->where('dashboard.summary.orders', 1)
            ->where('dashboard.summary.delivered', 1));
});

test('guests cannot export order reports', function () {
    $this->get(route('platform.reports.order-reports.export', [
        'format' => 'csv',
        'scope' => 'all',
    ]))->assertRedirect(route('login'));
});

test('platform admin can export order reports as csv', function () {
    $this->travelTo('2026-10-05 12:00:00');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'name' => 'Export Driver',
    ]);

    $order = Order::factory()->create([
        'status' => OrderStatus::Delivered,
        'total_amount' => 99,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $sydney->id,
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::Delivered,
    ]);
    $product = Product::factory()->create(['name' => 'Mint Ice']);

    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $order->fulfillment->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => 'Mint Ice',
        'sku_snapshot' => 'MINT-1',
        'product_snapshot' => [],
        'quantity' => 3,
        'unit_price' => 10,
        'subtotal' => 30,
    ]);

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports.export', [
            'format' => 'csv',
            'scope' => 'filters',
            'filter' => ['month' => '2026-10', 'compare' => '2026-09'],
        ]));

    $response->assertOk()->assertDownload('order-reports-filters-2026-10-05.csv');

    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))
        ->toContain('Order Reports')
        ->toContain('Mint Ice')
        ->toContain('Export Driver')
        ->toContain($sydney->name)
        ->toContain('Which branches sell most (September 2026)')
        ->toContain('Which drivers deliver most (September 2026)');
});

test('branch staff can open the branch order reports page with their drivers', function () {
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
        ->get(route('branch.reports.order-reports', $sydney))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('branch/reports/order-reports')
            ->has('drivers'));

    $driverIds = collect($response->viewData('page')['props']['drivers'])->pluck('id');

    expect($driverIds)->toContain($sydneyDriver->id)
        ->and($driverIds)->not->toContain($melbourneDriver->id);
});

test('order reports count cancelled, refunded and collected payments per month and compare two months', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(2);

    Order::factory()->create(['status' => OrderStatus::Cancelled]);

    $refunded = Order::factory()->create(['status' => OrderStatus::Delivered]);
    Payment::factory()->create([
        'order_id' => $refunded->id,
        'amount' => 30,
        'status' => PaymentStatus::Refunded,
    ]);

    $paid = Order::factory()->create(['status' => OrderStatus::Delivered]);
    Payment::factory()->create([
        'order_id' => $paid->id,
        'gateway' => 'pos',
        'amount' => 70,
        'status' => PaymentStatus::Verified,
    ]);

    $old = Order::factory()->create(['status' => OrderStatus::Cancelled]);
    $old->forceFill(['created_at' => $lastMonth])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['compare' => $lastMonth->format('Y-m')],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.month', now()->format('Y-m'))
            ->where('dashboard.summary.cancelled', 1)
            ->where('dashboard.summary.refunded', 1)
            ->where('dashboard.summary.refunded_amount', 30)
            ->where('dashboard.summary.payments_collected', 70)
            ->where('dashboard.compare.month', $lastMonth->format('Y-m'))
            ->where('dashboard.compare.summary.cancelled', 1)
            ->where('dashboard.compare.summary.orders', 1)
            ->has('dashboard.compare.products')
            ->has('dashboard.compare.branches')
            ->has('dashboard.compare.drivers'));
});

test('invalid or identical compare month is ignored and invalid month falls back to the current month', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['month' => 'nonsense', 'compare' => now()->format('Y-m')],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.month', now()->format('Y-m'))
            ->where('dashboard.compare', null));
});

test('compare product chart counts both months for each of the top five products', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $lastMonth = now()->subMonthNoOverflow()->startOfMonth()->addDays(2);

    $makeItem = function (string $name, int $quantity, bool $inLastMonth) use ($lastMonth): void {
        $order = Order::factory()->create();
        $fulfillment = OrderFulfillment::factory()->forOrder($order)->create();

        if ($inLastMonth) {
            $order->forceFill(['created_at' => $lastMonth])->saveQuietly();
        }

        OrderItem::query()->create([
            'order_id' => $order->id,
            'fulfillment_id' => $fulfillment->id,
            'product_id' => Product::factory()->create(['name' => $name])->id,
            'variant_id' => null,
            'product_name_snapshot' => $name,
            'sku_snapshot' => strtoupper($name),
            'product_snapshot' => [],
            'quantity' => $quantity,
            'unit_price' => 1,
            'subtotal' => $quantity,
        ]);
    };

    $makeItem('Alpha', 5, false);
    $makeItem('Alpha', 2, true);
    $makeItem('Bravo', 3, true);

    foreach (['C1', 'C2', 'C3', 'C4'] as $name) {
        $makeItem($name, 50, false);
        $makeItem($name, 50, true);
    }

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['compare' => $lastMonth->format('Y-m')],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dashboard.compare.product_comparison', 5)
            ->where('dashboard.compare.product_comparison.0.current', 50)
            ->where('dashboard.compare.product_comparison.0.previous', 50));

    $comparison = app(OrderReportService::class)
        ->platformDashboard(now()->format('Y-m'), $lastMonth->format('Y-m'))['compare']['product_comparison'];

    $alpha = collect($comparison)->firstWhere('name', 'Alpha');

    expect($alpha)->toMatchArray(['current' => 5, 'previous' => 2])
        ->and(collect($comparison)->pluck('name')->all())->not->toContain('Bravo');
});

test('quick range filter shows a rolling window and wins over the month filter', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Order::factory()->create();

    $older = Order::factory()->create();
    $older->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['range' => '7d', 'month' => '2020-01'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.range', '7d')
            ->where('dashboard.month', null)
            ->where('dashboard.label', 'Last 7 days')
            ->where('dashboard.summary.orders', 1));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', ['filter' => ['range' => '30d']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.label', 'Last 30 days')
            ->where('dashboard.summary.orders', 2));
});

test('range compare uses the previous period of the same length', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Order::factory()->create();

    $previous = Order::factory()->create();
    $previous->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['range' => '7d', 'compare' => 'previous'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.summary.orders', 1)
            ->where('dashboard.compare.label', 'Previous 7 days')
            ->where('dashboard.compare.summary.orders', 1)
            ->has('dashboard.compare.product_comparison'));
});

test('invalid range falls back to the current month and a month compare is ignored in range mode', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', ['filter' => ['range' => '5d']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.range', null)
            ->where('dashboard.month', now()->format('Y-m')));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', [
            'filter' => ['range' => '30d', 'compare' => '2026-01'],
        ]))
        ->assertInertia(fn (Assert $page) => $page->where('dashboard.compare', null));
});

test('order reports export names the range period', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $response = $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports.export', [
            'format' => 'csv',
            'scope' => 'filters',
            'filter' => ['range' => '90d', 'compare' => 'previous'],
        ]));

    $response->assertOk();

    expect(file_get_contents($response->baseResponse->getFile()->getPathname()))
        ->toContain('Last 90 days')
        ->toContain('Previous 90 days');
});

test('summary trend defaults to the previous month and the previous equal window', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Order::factory()->create();

    $lastMonth = Order::factory()->create();
    $lastMonth->forceFill(['created_at' => now()->subMonthNoOverflow()->startOfMonth()->addDay()])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.compare', null)
            ->where('dashboard.trend.label', now()->subMonthNoOverflow()->format('F Y'))
            ->where('dashboard.trend.summary.orders', 1));

    $tenDaysAgo = Order::factory()->create();
    $tenDaysAgo->forceFill(['created_at' => now()->subDays(10)])->saveQuietly();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', ['filter' => ['range' => '7d']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.trend.label', 'Previous 7 days')
            ->where('dashboard.trend.summary.orders', 1));
});

test('summary trend uses the explicit compare period when one is selected', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $compareMonth = now()->subMonthsNoOverflow(3)->format('Y-m');

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', ['filter' => ['compare' => $compareMonth]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.trend.label', now()->subMonthsNoOverflow(3)->format('F Y'))
            ->where('dashboard.trend.label', fn ($label) => $label === now()->subMonthsNoOverflow(3)->format('F Y')));
});

test('timeline lists every day of the month with orders and deliveries per day', function () {
    $this->travelTo('2026-11-17 12:00:00');

    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $order = Order::factory()->create(['status' => OrderStatus::Delivered]);
    OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Delivered]);
    Order::factory()->create();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('dashboard.timeline', 30)
            ->where('dashboard.timeline.0.label', 'Nov 1')
            ->where('dashboard.timeline.0.orders', 0)
            ->where('dashboard.timeline.16.label', 'Nov 17')
            ->where('dashboard.timeline.16.orders', 2)
            ->where('dashboard.timeline.16.delivered', 1));
});

test('one year range uses monthly timeline buckets and compare adds its own timeline', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.reports.order-reports', ['filter' => ['range' => '1y', 'compare' => 'previous']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('dashboard.timeline.0.label', now()->subYear()->format('M Y'))
            ->has('dashboard.compare.timeline')
            ->where('dashboard.timeline', fn ($timeline) => count($timeline) <= 13));
});
