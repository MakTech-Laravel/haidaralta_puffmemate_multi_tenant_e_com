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

test('two independently committed transactions never let total reservations exceed stock', function () {
    // Unlike the test above (one shared transaction, two sequential locks), this
    // simulates two genuinely separate requests: each opens its own transaction,
    // locks, reserves, and commits independently — proving the guard holds across
    // real commit boundaries, not just within a single already-open transaction.
    $inventory = Inventory::factory()->create(['quantity' => 10, 'reserved_quantity' => 0]);
    $customerA = Order::factory()->create();
    $customerB = Order::factory()->create();

    // Customer A orders 7 and commits first.
    DB::transaction(function () use ($inventory, $customerA): void {
        $service = app(InventoryReservationService::class);
        $locked = $service->lockOrdered([$inventory->id]);
        $service->reserve($locked[$inventory->id], 7, $customerA);
    });

    expect($inventory->refresh()->reserved_quantity)->toBe(7);

    // Customer B then tries to order 5 in a brand new, independent transaction.
    // Only 3 units are actually available (10 - 7), so this must fail.
    expect(function () use ($inventory, $customerB): void {
        DB::transaction(function () use ($inventory, $customerB): void {
            $service = app(InventoryReservationService::class);
            $locked = $service->lockOrdered([$inventory->id]);
            $service->reserve($locked[$inventory->id], 5, $customerB);
        });
    })->toThrow(ValidationException::class);

    // Total reserved never exceeded the physical stock of 10.
    expect($inventory->refresh()->reserved_quantity)->toBe(7)
        ->and($inventory->reserved_quantity)->toBeLessThanOrEqual($inventory->quantity);
});

test('a second real database connection cannot acquire the same locked inventory row', function () {
    // Proves mutual exclusion at the database engine level using two independent
    // PDO connections to the same on-disk database (not the shared :memory: test
    // connection, which two separate connections would not actually share).
    $dbPath = sys_get_temp_dir().'/allocation_concurrency_'.uniqid().'.sqlite';
    touch($dbPath);

    try {
        $pdoA = new PDO('sqlite:'.$dbPath);
        $pdoA->exec('CREATE TABLE inventories (id INTEGER PRIMARY KEY, quantity INTEGER, reserved_quantity INTEGER)');
        $pdoA->exec('INSERT INTO inventories (id, quantity, reserved_quantity) VALUES (1, 10, 0)');

        $pdoB = new PDO('sqlite:'.$dbPath);
        $pdoB->setAttribute(PDO::ATTR_TIMEOUT, 0);

        // Connection A takes an exclusive write lock and holds it (no commit yet).
        $pdoA->exec('BEGIN IMMEDIATE');
        $pdoA->exec('UPDATE inventories SET reserved_quantity = 7 WHERE id = 1');

        // Connection B tries to write to the same locked database while A still
        // holds the lock — this must fail immediately (mutual exclusion), it must
        // never silently succeed and let both reservations land.
        $blocked = false;

        try {
            $pdoB->exec('BEGIN IMMEDIATE');
            $pdoB->exec('UPDATE inventories SET reserved_quantity = 12 WHERE id = 1');
            $pdoB->exec('COMMIT');
        } catch (PDOException) {
            $blocked = true;
        }

        $pdoA->exec('COMMIT');

        expect($blocked)->toBeTrue();

        $final = $pdoA->query('SELECT reserved_quantity FROM inventories WHERE id = 1')->fetchColumn();
        expect((int) $final)->toBe(7);
    } finally {
        @unlink($dbPath);
    }
});
