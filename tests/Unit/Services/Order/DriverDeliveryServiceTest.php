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
use Illuminate\Support\Facades\DB;

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
