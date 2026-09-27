<?php

use App\Enums\FulfillmentStatus;
use App\Enums\PaymentStatus;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
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

    $orderTwo = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($orderTwo)->create(['branch_id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/orders/index')
            ->has('table.data', 2));
});

test('platform order detail exposes every fulfillment across branches', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $sydney = Branch::query()->where('slug', BranchSeeder::SYDNEY_CBD)->firstOrFail();
    $other = Branch::factory()->create();

    $order = Order::factory()->create();
    OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $sydney->id]);
    OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $other->id]);

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/orders/show')
            ->has('order.fulfillments', 2));
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
            'status' => FulfillmentStatus::Delivered->value,
        ])
        ->assertRedirect(route('platform.orders.show', $order));

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Delivered)
        ->and($order->refresh()->status->value)->toBe('delivered');
});

test('platform admin can override the master orders payment status', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();

    $order = Order::factory()->create(['payment_status' => PaymentStatus::Unpaid]);
    OrderFulfillment::factory()->forOrder($order)->create();

    $this->actingAs($admin, 'platform')
        ->put(route('platform.orders.payment-status', $order), [
            'payment_status' => PaymentStatus::Paid->value,
        ])
        ->assertRedirect(route('platform.orders.show', $order));

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Paid);
});
