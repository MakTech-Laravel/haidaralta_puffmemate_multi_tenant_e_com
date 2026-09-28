<?php

use App\Enums\InventoryTransactionType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Order;
use App\Services\Inventory\InventoryReservationService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

test('reserve reduces available quantity and writes a ledger row', function () {
    $inventory = Inventory::factory()->create(['quantity' => 10, 'reserved_quantity' => 0]);
    $order = Order::factory()->create();

    DB::transaction(function () use ($inventory, $order): void {
        $locked = app(InventoryReservationService::class)->lockOrdered([$inventory->id]);
        app(InventoryReservationService::class)->reserve($locked[$inventory->id], 4, $order);
    });

    expect($inventory->refresh()->reserved_quantity)->toBe(4)
        ->and($inventory->quantity)->toBe(10);

    $this->assertDatabaseHas('inventory_transactions', [
        'product_id' => $inventory->product_id,
        'type' => InventoryTransactionType::Reserve->value,
        'quantity' => 4,
        'reference_id' => $order->id,
    ]);
});

test('a second reserve in the same lock fails when available stock is gone', function () {
    $inventory = Inventory::factory()->create(['quantity' => 5, 'reserved_quantity' => 0]);
    $first = Order::factory()->create();
    $second = Order::factory()->create();

    expect(function () use ($inventory, $first, $second): void {
        DB::transaction(function () use ($inventory, $first, $second): void {
            $service = app(InventoryReservationService::class);
            $locked = $service->lockOrdered([$inventory->id]);
            $service->reserve($locked[$inventory->id], 3, $first);
            $service->reserve($locked[$inventory->id], 3, $second);
        });
    })->toThrow(ValidationException::class);

    expect($inventory->refresh()->reserved_quantity)->toBe(0);
    expect(InventoryTransaction::query()->count())->toBe(0);
});
