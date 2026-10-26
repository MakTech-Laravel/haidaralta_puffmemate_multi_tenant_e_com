<?php

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\DriverDeliveryService;
use Inertia\Testing\AssertableInertia as Assert;

/**
 * @return array{0: Order, 1: OrderItem, 2: Inventory}
 */
function assignedOrderWithLateLine(User $driver): array
{
    $order = driverPortalOrder($driver, ['status' => FulfillmentStatus::Assigned]);
    $fulfillment = $order->fulfillment;
    $late = Product::factory()->create(['name' => 'Late Stick']);

    $lateItem = OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $late->id,
        'variant_id' => null,
        'product_name_snapshot' => $late->name,
        'sku_snapshot' => $late->sku,
        'product_snapshot' => ['name' => $late->name],
        'quantity' => 2,
        'unit_price' => 10,
        'subtotal' => 20,
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

    return [$order->refresh(), $lateItem, $inventory];
}

test('driver cannot pick up while a late line is still unresolved', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    [$order] = assignedOrderWithLateLine($driver);

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.pick-up', $order))
        ->assertRedirect(route('driver.orders.show', $order))
        ->assertSessionHasErrors([
            'status' => DriverDeliveryService::LATE_ITEMS_BLOCK_PICKUP_MESSAGE,
        ]);

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::Assigned);
});

test('driver order detail shows a disabled pick-up and late-items warning while unresolved', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    [$order] = assignedOrderWithLateLine($driver);

    $this->actingAs($driver, 'driver')
        ->get(route('driver.orders.show', $order))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('driver/orders/show')
            ->where('order.canPickUp', false)
            ->where('order.hasUnresolvedLateItems', true)
            ->where(
                'order.lateItemsBlockMessage',
                DriverDeliveryService::LATE_ITEMS_BLOCK_PICKUP_MESSAGE,
            )
            ->where('order.nextAction.key', 'pick_up')
            ->where('order.nextAction.disabled', true));
});

test('driver can pick up after collecting the late line', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    [$order, $lateItem] = assignedOrderWithLateLine($driver);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.items.collect-late', [$order, $lateItem]))
        ->assertRedirect();

    expect($lateItem->fresh()->is_late_delivery)->toBeFalse();

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.pick-up', $order))
        ->assertRedirect(route('driver.orders.show', $order))
        ->assertSessionHasNoErrors()
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Order picked up.',
        ]);

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::PickedUp);
});

test('driver can pick up after removing the late line', function () {
    $driver = User::factory()->create([
        'user_type' => UserType::DRIVER,
        'email_verified_at' => now(),
    ]);
    [$order, $lateItem] = assignedOrderWithLateLine($driver);

    $this->actingAs($driver, 'driver')
        ->post(route('driver.orders.items.remove-late', [$order, $lateItem]))
        ->assertRedirect();

    expect($lateItem->fresh()->removed_at)->not->toBeNull();

    $this->actingAs($driver, 'driver')
        ->from(route('driver.orders.show', $order))
        ->post(route('driver.orders.pick-up', $order))
        ->assertRedirect(route('driver.orders.show', $order))
        ->assertSessionHasNoErrors();

    expect($order->fulfillment->fresh()->status)->toBe(FulfillmentStatus::PickedUp);
});
