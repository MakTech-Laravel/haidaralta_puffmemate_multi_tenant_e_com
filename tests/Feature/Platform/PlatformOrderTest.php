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
            ->has('table.data.0.status_label'));
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
            ->has('order.drivers')
            ->has('order.statusLabel')
            ->has('order.paymentStatusLabel')
            ->has('order.sourceLabel')
            ->where('order.sourceLabel', 'Customer')
            ->where('order.paymentStatusLabel', $order->payment_status->label()));
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
            'status' => FulfillmentStatus::Processing->value,
        ])
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Processing)
        ->and($order->refresh()->status->value)->toBe('processing');
});

test('platform admin can override the master orders payment status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::PendingCollection]);
    OrderFulfillment::factory()->forOrder($order)->create();
    Payment::factory()->create(['order_id' => $order->id, 'status' => PaymentStatus::PendingCollection]);

    $this->actingAs($admin, 'platform')
        ->put(route('platform.orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Collected->value,
        ])
        ->assertRedirect(route('platform.orders.show', $order));

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Collected);
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

test('platform admin can retry reschedule and confirm a returned delivery for any branch', function () {
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
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::OutForDelivery);

    $fulfillment->forceFill(['status' => FulfillmentStatus::DeliveryFailed])->save();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.return-delivery', $order))
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Returning);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.confirm-return', $order))
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Cancelled);
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
