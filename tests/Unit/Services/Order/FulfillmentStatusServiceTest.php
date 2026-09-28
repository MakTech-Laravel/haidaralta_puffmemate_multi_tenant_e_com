<?php

use App\Enums\FulfillmentStatus;
use App\Enums\UserType;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Order\FulfillmentStatusService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('staff cannot cancel after the driver has picked the order up', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::PickedUp,
    ]);

    expect(fn () => app(FulfillmentStatusService::class)->cancel($fulfillment, $staff))
        ->toThrow(ValidationException::class);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::PickedUp);
});

test('cancelling a pending order releases reserved stock', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $product = Product::factory()->create();
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Pending]);
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

    app(FulfillmentStatusService::class)->cancel($fulfillment, $staff);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Cancelled)
        ->and($inventory->refresh()->reserved_quantity)->toBe(0)
        ->and($order->refresh()->status->value)->toBe('cancelled');
});
