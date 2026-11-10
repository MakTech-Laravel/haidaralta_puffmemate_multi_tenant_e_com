<?php

use App\Enums\FulfillmentStatus;
use App\Enums\NotificationType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Models\UserNotification;
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

/**
 * @return array{0: Order, 1: OrderItem, 2: OrderItem, 3: Inventory}
 */
function lateOrderWithTwoLines(User $customer): array
{
    $keep = Product::factory()->create(['name' => 'Keep Stick']);
    $late = Product::factory()->create(['name' => 'Late Stick']);

    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Placed,
        'subtotal' => 90,
        'discount' => 0,
        'delivery_fee' => 10,
        'total_amount' => 100,
        'payment_status' => PaymentStatus::PendingCollection,
        'accepts_late_delivery' => true,
    ]);

    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Placed,
    ]);

    $keepItem = OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $keep->id,
        'variant_id' => null,
        'product_name_snapshot' => $keep->name,
        'sku_snapshot' => $keep->sku,
        'product_snapshot' => ['name' => $keep->name],
        'quantity' => 1,
        'unit_price' => 40,
        'subtotal' => 40,
        'is_late_delivery' => false,
        'local_reserved_quantity' => 1,
    ]);

    $lateItem = OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $late->id,
        'variant_id' => null,
        'product_name_snapshot' => $late->name,
        'sku_snapshot' => $late->sku,
        'product_snapshot' => ['name' => $late->name],
        'quantity' => 2,
        'unit_price' => 25,
        'subtotal' => 50,
        'is_late_delivery' => true,
        'local_reserved_quantity' => 1,
    ]);

    $inventory = Inventory::factory()->create([
        'branch_id' => $fulfillment->branch_id,
        'product_id' => $late->id,
        'variant_id' => null,
        'quantity' => 5,
        'reserved_quantity' => 1,
        'status' => true,
    ]);

    Inventory::factory()->create([
        'branch_id' => $fulfillment->branch_id,
        'product_id' => $keep->id,
        'variant_id' => null,
        'quantity' => 5,
        'reserved_quantity' => 1,
        'status' => true,
    ]);

    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
        'amount' => 100,
    ]);

    return [$order->refresh(), $keepItem, $lateItem, $inventory];
}

test('platform staff can remove a late line, recalc totals, notify, and show removal to the customer', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    [$order, $keepItem, $lateItem, $inventory] = lateOrderWithTwoLines($customer);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.items.remove-late', [$order, $lateItem]))
        ->assertRedirect(route('platform.orders.show', $order));

    $lateItem->refresh();
    $order->refresh();

    expect($lateItem->removed_at)->not->toBeNull()
        ->and($lateItem->removed_by)->toBe($admin->id)
        ->and((int) $lateItem->local_reserved_quantity)->toBe(0)
        ->and((float) $order->subtotal)->toBe(40.0)
        ->and((float) $order->delivery_fee)->toBe(10.0)
        ->and((float) $order->total_amount)->toBe(50.0)
        ->and($order->status)->toBe(OrderStatus::Placed)
        ->and($keepItem->fresh()->removed_at)->toBeNull()
        ->and((int) $inventory->fresh()->reserved_quantity)->toBe(0)
        ->and(UserNotification::query()
            ->where('user_id', $customer->id)
            ->whereHas('notification', fn ($query) => $query
                ->where('type', NotificationType::Order)
                ->where('title', 'Item removed from order'))
            ->exists())->toBeTrue();

    $this->actingAs($customer, 'customer')
        ->get(route('customer.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('customer/orders/show')
            ->where('order.products.0.isRemoved', false)
            ->where('order.products.1.isRemoved', true)
            ->where('order.products.1.name', 'Late Stick')
            ->where('order.totalLabel', '$50.00'));
});

test('removing the last active late line cancels the order and notifies the customer', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    $product = Product::factory()->create(['name' => 'Only Late Stick']);
    $order = Order::factory()->for($customer, 'customer')->create([
        'status' => OrderStatus::Placed,
        'subtotal' => 50,
        'discount' => 0,
        'delivery_fee' => 10,
        'total_amount' => 60,
        'payment_status' => PaymentStatus::PendingCollection,
        'accepts_late_delivery' => true,
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Placed,
    ]);
    $item = OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 2,
        'unit_price' => 25,
        'subtotal' => 50,
        'is_late_delivery' => true,
        'local_reserved_quantity' => 1,
    ]);
    Inventory::factory()->create([
        'branch_id' => $fulfillment->branch_id,
        'product_id' => $product->id,
        'quantity' => 5,
        'reserved_quantity' => 1,
        'status' => true,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::PendingCollection,
        'gateway' => 'cod',
        'amount' => 60,
    ]);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.items.remove-late', [$order, $item]))
        ->assertRedirect(route('platform.orders.show', $order));

    expect($item->fresh()->removed_at)->not->toBeNull()
        ->and($order->fresh()->status)->toBe(OrderStatus::Cancelled)
        ->and($fulfillment->fresh()->status)->toBe(FulfillmentStatus::Cancelled)
        ->and(UserNotification::query()
            ->where('user_id', $customer->id)
            ->whereHas('notification', fn ($query) => $query
                ->where('type', NotificationType::Order)
                ->where('title', 'Order cancelled'))
            ->exists())->toBeTrue();
});

test('non-late items cannot be removed via the late-item endpoint', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    [$order, $keepItem] = lateOrderWithTwoLines($customer);

    $this->actingAs($admin, 'platform')
        ->from(route('platform.orders.show', $order))
        ->post(route('platform.orders.items.remove-late', [$order, $keepItem]))
        ->assertRedirect(route('platform.orders.show', $order))
        ->assertSessionHasErrors('item');

    expect($keepItem->fresh()->removed_at)->toBeNull();
});

test('platform staff can collect a late line, reserve shortfall, clear late flag, and notify', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    [$order, $keepItem, $lateItem, $inventory] = lateOrderWithTwoLines($customer);

    // Only the local unit is on the shelf — collect must stock-in the shortfall.
    $inventory->forceFill([
        'quantity' => 1,
        'reserved_quantity' => 1,
    ])->save();

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.items.collect-late', [$order, $lateItem]))
        ->assertRedirect(route('platform.orders.show', $order));

    $lateItem->refresh();
    $inventory->refresh();
    $order->refresh();

    expect($lateItem->is_late_delivery)->toBeFalse()
        ->and((int) $lateItem->local_reserved_quantity)->toBe(2)
        ->and($lateItem->removed_at)->toBeNull()
        ->and((int) $inventory->quantity)->toBe(2)
        ->and((int) $inventory->reserved_quantity)->toBe(2)
        ->and($order->accepts_late_delivery)->toBeFalse()
        ->and(UserNotification::query()
            ->where('user_id', $customer->id)
            ->whereHas('notification', fn ($query) => $query
                ->where('type', NotificationType::Order)
                ->where('title', 'Item ready for your order'))
            ->exists())->toBeTrue();

    $this->actingAs($admin, 'platform')
        ->get(route('platform.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('platform/orders/show')
            ->where('order.fulfillments.0.items.1.canRemoveLate', false)
            ->where('order.fulfillments.0.items.1.canCollectLate', false)
            ->where('order.fulfillments.0.items.1.isLateDelivery', false));
});

test('collected late lines cannot be collected again', function () {
    $admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);

    [$order, , $lateItem] = lateOrderWithTwoLines($customer);

    $this->actingAs($admin, 'platform')
        ->post(route('platform.orders.items.collect-late', [$order, $lateItem]))
        ->assertRedirect(route('platform.orders.show', $order));

    $this->actingAs($admin, 'platform')
        ->from(route('platform.orders.show', $order))
        ->post(route('platform.orders.items.collect-late', [$order, $lateItem]))
        ->assertRedirect(route('platform.orders.show', $order))
        ->assertSessionHasErrors('item');
});
