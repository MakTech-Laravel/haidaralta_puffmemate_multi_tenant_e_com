<?php

use App\Enums\FulfillmentStatus;
use App\Enums\InventoryTransactionType;
use App\Enums\OrderReturnType;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\UserType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Order\OrderReturnService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('an unpaid arrived order is cancelled and its reservation released when a return is approved', function () {
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER, 'branch_id' => null]);
    $staff = User::factory()->create(['user_type' => UserType::PLATFORM, 'branch_id' => null]);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Arrived,
        'payment_status' => PaymentStatus::PendingCollection,
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Arrived,
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

    $return = app(OrderReturnService::class)->request($order, $customer, OrderReturnType::Return, 'Damaged');
    app(OrderReturnService::class)->approve($return, $staff);

    expect($fulfillment->refresh()->status)->toBe(FulfillmentStatus::Cancelled)
        ->and($inventory->refresh()->reserved_quantity)->toBe(0)
        ->and($return->refresh()->status->value)->toBe('approved');
});

test('a collected order is refunded and restocked when a return is approved', function () {
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER, 'branch_id' => null]);
    $staff = User::factory()->create(['user_type' => UserType::PLATFORM, 'branch_id' => null]);
    $product = Product::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Delivered,
        'payment_status' => PaymentStatus::Collected,
    ]);
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Delivered,
    ]);
    Payment::factory()->create([
        'order_id' => $order->id,
        'status' => PaymentStatus::Collected,
        'amount' => 20,
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
        'quantity' => 3,
        'reserved_quantity' => 0,
    ]);

    $return = app(OrderReturnService::class)->request($order, $customer, OrderReturnType::Refund, 'Wrong item');
    app(OrderReturnService::class)->approve($return, $staff);

    expect($order->refresh()->payment_status)->toBe(PaymentStatus::Refunded)
        ->and($inventory->refresh()->quantity)->toBe(5)
        ->and(InventoryTransaction::query()->where('product_id', $product->id)->where('type', InventoryTransactionType::StockIn)->exists())->toBeTrue();
});

test('a return cannot be requested before arrival', function () {
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER, 'branch_id' => null]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Processing,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Processing,
    ]);

    expect(fn () => app(OrderReturnService::class)->request($order, $customer, OrderReturnType::Refund, 'Too soon'))
        ->toThrow(ValidationException::class);
});

test('staff can reject a return request', function () {
    $customer = User::factory()->create(['user_type' => UserType::CUSTOMER, 'branch_id' => null]);
    $staff = User::factory()->create(['user_type' => UserType::BRANCH]);
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Delivered,
    ]);
    OrderFulfillment::factory()->forOrder($order)->create([
        'status' => FulfillmentStatus::Delivered,
    ]);

    $return = app(OrderReturnService::class)->request($order, $customer, OrderReturnType::Return, 'Changed mind');
    app(OrderReturnService::class)->reject($return, $staff);

    expect($return->refresh()->status->value)->toBe('rejected')
        ->and($order->refresh()->status)->toBe(OrderStatus::Delivered);
});
