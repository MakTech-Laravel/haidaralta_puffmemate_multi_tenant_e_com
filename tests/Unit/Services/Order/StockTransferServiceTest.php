<?php

use App\Enums\StockTransferStatus;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\InventoryReservationService;
use App\Services\Order\StockTransferService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('a transfer moves from requested through receive and relocates reserved stock', function () {
    $source = Branch::factory()->create();
    $destination = Branch::factory()->create();
    $product = Product::factory()->create();
    $actor = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $source->id]);
    $receiver = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $destination->id]);
    $inventory = Inventory::factory()->create([
        'branch_id' => $source->id,
        'product_id' => $product->id,
        'quantity' => 8,
        'reserved_quantity' => 0,
    ]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $destination->id]);

    DB::transaction(function () use ($inventory, $order): void {
        $service = app(InventoryReservationService::class);
        $locked = $service->lockOrdered([$inventory->id]);
        $service->reserve($locked[$inventory->id], 2, $order);
    });

    $transfers = app(StockTransferService::class);
    $transfer = $transfers->createForFulfillment($fulfillment, [
        'source_branch_id' => $source->id,
        'destination_branch_id' => $destination->id,
        'distance_km' => 12,
        'eta_hours' => 2,
        'estimated_cost' => 5,
        'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 2]],
    ]);

    $transfers->approve($transfer, $actor);
    $transfers->pick($transfer->refresh(), $actor);
    $transfers->dispatch($transfer->refresh(), $actor);
    $transfers->receive($transfer->refresh(), $receiver);

    expect($transfer->refresh()->status)->toBe(StockTransferStatus::Received)
        ->and($inventory->refresh()->quantity)->toBe(6)
        ->and($inventory->reserved_quantity)->toBe(0);

    $destinationStock = Inventory::query()
        ->where('branch_id', $destination->id)
        ->where('product_id', $product->id)
        ->firstOrFail();

    expect($destinationStock->quantity)->toBe(2)
        ->and($destinationStock->reserved_quantity)->toBe(2);
});

test('a transfer cannot be cancelled after it is dispatched', function () {
    $source = Branch::factory()->create();
    $destination = Branch::factory()->create();
    $product = Product::factory()->create();
    $actor = User::factory()->create(['user_type' => UserType::BRANCH, 'branch_id' => $source->id]);
    $order = Order::factory()->create();
    $fulfillment = OrderFulfillment::factory()->forOrder($order)->create(['branch_id' => $destination->id]);

    $transfers = app(StockTransferService::class);
    $transfer = $transfers->createForFulfillment($fulfillment, [
        'source_branch_id' => $source->id,
        'destination_branch_id' => $destination->id,
        'distance_km' => 4,
        'eta_hours' => 1,
        'estimated_cost' => 0,
        'items' => [['product_id' => $product->id, 'variant_id' => null, 'quantity' => 1]],
    ]);

    $transfers->approve($transfer, $actor);
    $transfers->pick($transfer->refresh(), $actor);
    $transfers->dispatch($transfer->refresh(), $actor);

    expect(fn () => $transfers->cancel($transfer->refresh(), $actor))
        ->toThrow(ValidationException::class);

    expect($transfer->refresh()->status)->toBe(StockTransferStatus::InTransit);
});
