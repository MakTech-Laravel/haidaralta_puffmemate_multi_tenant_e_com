<?php

use App\Enums\DeliveryFailureReason;
use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Order\DriverDeliveryService;
use App\Services\Sms\FakeSmsSender;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('a failed delivery keeps reserved stock instead of releasing it', function () {
    $driver = User::factory()->create(['user_type' => UserType::DRIVER, 'branch_id' => null]);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'shipping_address_snapshot' => [
            'name' => 'Ada Lovelace',
            'phone' => '+61410111222',
            'address' => '1 Test Street',
            'city' => 'Sydney',
        ],
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::OutForDelivery,
    ]);
    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 2,
        'unit_price' => 10,
        'subtotal' => 20,
    ]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $fulfillment->branch_id,
        'product_id' => $product->id,
        'quantity' => 5,
        'reserved_quantity' => 0,
    ]);

    DB::transaction(function () use ($inventory, $order): void {
        $service = app(InventoryReservationService::class);
        $locked = $service->lockOrdered([$inventory->id]);
        $service->reserve($locked[$inventory->id], 2, $order);
    });

    app(DriverDeliveryService::class)->fail(
        $fulfillment,
        $driver,
        DeliveryFailureReason::CustomerUnavailable,
    );

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::DeliveryFailed)
        ->and($inventory->refresh()->reserved_quantity)->toBe(2)
        ->and($inventory->quantity)->toBe(5)
        ->and($fulfillment->deliveryAttempts)->toHaveCount(1);
});

function failedDeliveryFulfillment(): array
{
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
        'driver_id' => $driver->id,
        'status' => FulfillmentStatus::DeliveryFailed,
    ]);

    return [$fulfillment, $driver];
}

test('retry sends the same driver back out with a new handover otp', function () {
    [$fulfillment, $driver] = failedDeliveryFulfillment();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);

    $result = app(DriverDeliveryService::class)->retry($fulfillment, $staff);

    expect($result->status)->toBe(FulfillmentStatus::OutForDelivery)
        ->and($result->driver_id)->toBe($driver->id)
        ->and(app(FakeSmsSender::class)->lastCode())->not->toBeNull();
});

test('retry without an assigned driver is rejected', function () {
    [$fulfillment] = failedDeliveryFulfillment();
    $fulfillment->forceFill(['driver_id' => null])->save();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);

    expect(fn () => app(DriverDeliveryService::class)->retry($fulfillment, $staff))
        ->toThrow(ValidationException::class);
});

test('reschedule clears the driver and returns the fulfillment to ready', function () {
    [$fulfillment] = failedDeliveryFulfillment();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);

    $result = app(DriverDeliveryService::class)->reschedule($fulfillment, $staff);

    expect($result->status)->toBe(FulfillmentStatus::Ready)
        ->and($result->driver_id)->toBeNull();
});

test('begin return moves a failed delivery to returning', function () {
    [$fulfillment] = failedDeliveryFulfillment();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);

    $result = app(DriverDeliveryService::class)->beginReturn($fulfillment, $staff);

    expect($result->status)->toBe(FulfillmentStatus::Returning);
});

test('confirming a return cancels the fulfillment and releases stock', function () {
    [$fulfillment, $driver] = failedDeliveryFulfillment();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $product = Product::factory()->create();
    $order = $fulfillment->order;
    OrderItem::query()->create([
        'order_id' => $order->id,
        'fulfillment_id' => $fulfillment->id,
        'product_id' => $product->id,
        'variant_id' => null,
        'product_name_snapshot' => $product->name,
        'sku_snapshot' => $product->sku,
        'product_snapshot' => ['name' => $product->name],
        'quantity' => 2,
        'unit_price' => 10,
        'subtotal' => 20,
    ]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $fulfillment->branch_id,
        'product_id' => $product->id,
        'quantity' => 5,
        'reserved_quantity' => 0,
    ]);
    DB::transaction(function () use ($inventory, $order): void {
        $service = app(InventoryReservationService::class);
        $locked = $service->lockOrdered([$inventory->id]);
        $service->reserve($locked[$inventory->id], 2, $order);
    });

    app(DriverDeliveryService::class)->beginReturn($fulfillment, $staff);
    $result = app(DriverDeliveryService::class)->confirmReturn($fulfillment->refresh(), $staff);

    expect($result->status)->toBe(FulfillmentStatus::Cancelled)
        ->and($result->driver_id)->toBeNull()
        ->and($inventory->refresh()->reserved_quantity)->toBe(0);
});

test('confirming a return before the driver reports it back is rejected', function () {
    [$fulfillment] = failedDeliveryFulfillment();
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);

    expect(fn () => app(DriverDeliveryService::class)->confirmReturn($fulfillment, $staff))
        ->toThrow(ValidationException::class);
});
