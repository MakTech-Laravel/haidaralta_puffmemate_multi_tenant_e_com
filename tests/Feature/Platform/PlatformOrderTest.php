<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Payment;
use App\Models\User;
use App\Support\Phone;
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

test('platform admin sees orders across every branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $orderOne = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($orderOne)->create(['branch_id' => $sydney->id]);

    $orderTwo = Order::factory()->create([
        'payment_status' => PaymentStatus::PendingCollection,
    ]);
    OrderFulfillment::factory()->forOrder($orderTwo)->create(['branch_id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/orders/index')
            ->has('table.data', 2)
            ->where('table.data.0.payment_status', PaymentStatus::PendingCollection->value)
            ->where('table.data.0.payment_status_label', 'Pending collection')
            ->where('table.data.0.source_label', 'Customer')
            ->has('table.data.0.status_label')
            ->where('table.data.0.can_assign', false)
            ->where('table.data.0.assign_status', 'not_assign')
            ->where('table.data.1.can_assign', false)
            ->where('table.data.1.assign_status', 'not_assign'));
});

test('platform order detail exposes the single fulfillment', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();

    $order = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $sydney->id]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/orders/show')
            ->has('order.fulfillments', 1)
            ->where('order.fulfillments.0.canAssign', false)
            ->has('order.statusLabel')
            ->has('order.paymentStatusLabel')
            ->has('order.sourceLabel')
            ->where('order.sourceLabel', 'Customer')
            ->where('order.paymentStatusLabel', $order->payment_status->label())
            ->where('order.customerId', $order->customer_id)
            ->where('order.customerPhone', $order->customer?->phone));
});

test('platform admin can update any branchs fulfillment status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $other = Branch::factory()->create();

    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $other->id,
        'status' => FulfillmentStatus::Pending,
    ]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.orders.fulfillments.status', [$order, $fulfillment]), [
            'status' => FulfillmentStatus::Placed->value,
        ])
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Placed)
        ->and($order->refresh()->status->value)->toBe('placed');
});

test('platform admin cannot override payment outside handover', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Collected->value,
        ])
        ->assertSessionHasErrors('payment_status');

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::PendingCollection);
});

test('the platform override rejects verifying an uncollected payment', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Verified->value,
        ])
        ->assertSessionHasErrors('payment_status');

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::PendingCollection);
});

test('platform admin receives goods back instead of retrying a failed delivery', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $other = Branch::factory()->create();
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);

    $order = Order::factory()->create([
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410111222',
            'address' => '1 Test Street',
            'city' => 'Sydney',
        ],
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'branch_id' => $other->id,
        'status' => FulfillmentStatus::DeliveryFailed,
        'driver_id' => $driver->id,
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.retry-delivery', $order))
        ->assertSessionHasErrors('status');

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::DeliveryFailed);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.return-delivery', $order))
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Returning);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.confirm-return', $order))
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Returned)
        ->and($fulfillment->driver_id)->toBeNull();
});

test('platform orders can be filtered by source', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    Order::factory()->create(['source' => OrderSource::CustomerCheckout]);
    $driverSale = Order::factory()->create(['source' => OrderSource::DriverSale]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index', [
            'filter' => ['source' => OrderSource::DriverSale->value],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $driverSale->id)
            ->where('table.data.0.source_label', 'Driver Sale'));
});

test('platform orders list the customer phone and placed time', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'branch_id' => null,
        'name' => 'Listed Buyer',
        'phone' => Phone::toE164('0499333444'),
    ]);
    $placed = now()->subDay()->startOfDay()->addHours(12);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'created_at' => $placed,
    ]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('table.data.0.id', $order->id)
            ->where('table.data.0.customer_phone', $customer->phone)
            ->where('table.data.0.customer_id', $customer->id)
            ->where('table.data.0.created_at', $placed->toIso8601String()));
});

test('platform orders can be searched by order number customer and branch', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $branch = Branch::factory()->create(['name' => 'North Wharf Store']);
    $customer = User::factory()->create([
        'user_type' => UserType::CUSTOMER,
        'branch_id' => null,
        'name' => 'Nora Search',
        'email' => 'nora.search@example.com',
        'phone' => Phone::toE164('0499555666'),
    ]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'order_number' => 'PMM-SEARCH-1001',
    ]);
    OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $branch->id]);
    Order::factory()->create(['order_number' => 'PMM-OTHER-2002']);

    $index = route('platform.orders.index');

    foreach ([
        'PMM-SEARCH-1001',
        'Nora Search',
        'nora.search@example.com',
        '0499555666',
        'North Wharf',
    ] as $term) {
        $this->actingAs($admin, 'platform')
            ->get($index.'?'.http_build_query(['filter' => ['search' => $term]]))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->has('table.data', 1)
                ->where('table.data.0.id', $order->id));
    }
});

test('platform orders filter by a single placed day or a range and ignore a future date', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $yesterday = now()->subDay()->startOfDay()->addHours(12);
    $today = now()->startOfDay()->addHours(12);
    $older = now()->subDays(5)->startOfDay()->addHours(12);

    $yesterdayOrder = Order::factory()->create(['created_at' => $yesterday]);
    $todayOrder = Order::factory()->create(['created_at' => $today]);
    Order::factory()->create(['created_at' => $older]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index', [
            'filter' => ['from' => $yesterday->toDateString()],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 1)
            ->where('table.data.0.id', $yesterdayOrder->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index', [
            'filter' => [
                'from' => $older->toDateString(),
                'to' => $yesterday->toDateString(),
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 2)
            ->where('table.data.0.id', $yesterdayOrder->id)
            ->where('table.data.1.id', Order::query()->whereDate('created_at', $older->toDateString())->value('id')));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index', [
            'filter' => ['from' => now()->addDay()->toDateString()],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('table.data', 3)
            ->where('table.data.0.id', $todayOrder->id));

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index', [
            'filter' => [
                'from' => $yesterday->toDateString(),
                'to' => $older->toDateString(),
            ],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('table.data', 3));
});
