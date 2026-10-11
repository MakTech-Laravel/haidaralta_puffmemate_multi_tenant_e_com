<?php

use App\Enums\FulfillmentStatus;
use App\Enums\OrderStatus;
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

test('a customer cannot cancel a ready order but staff can', function () {
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER]);
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Ready]);

    expect(fn () => app(FulfillmentStatusService::class)->transition($fulfillment, FulfillmentStatus::Cancelled, $customer))
        ->toThrow(ValidationException::class);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Ready);

    app(FulfillmentStatusService::class)->transition($fulfillment, FulfillmentStatus::Cancelled, $staff);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Cancelled);
});

test('cancel is rejected once an order is delivered', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Delivered]);

    expect(fn () => app(FulfillmentStatusService::class)->cancel($fulfillment, $staff))
        ->toThrow(ValidationException::class);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Delivered);
});

test('pending confirmation cannot skip straight to packaging', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Pending]);

    expect(fn () => app(FulfillmentStatusService::class)->transition($fulfillment, FulfillmentStatus::Processing, $staff))
        ->toThrow(ValidationException::class);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Pending);
});

test('confirming a pending order records who placed it', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH, 'name' => 'Branch Clerk']);
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Pending]);

    app(FulfillmentStatusService::class)->transition($fulfillment, FulfillmentStatus::Placed, $staff, 'Confirmed by phone');

    $history = $fulfillment->statusHistories()->first();

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Placed)
        ->and($order->refresh()->status->value)->toBe('placed')
        ->and($history->changed_by)->toBe($staff->id)
        ->and($history->old_status)->toBe('pending')
        ->and($history->new_status)->toBe('placed')
        ->and($history->comment)->toBe('Confirmed by phone');
});

test('a status jump outside the graph is rejected', function () {
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['status' => FulfillmentStatus::Pending]);

    expect(fn () => app(FulfillmentStatusService::class)->transition($fulfillment, FulfillmentStatus::Delivered, $staff))
        ->toThrow(ValidationException::class);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Pending);
});
