<?php

namespace App\Services\Driver;

use App\Enums\DriverAssignmentStatus;
use App\Enums\DriverStockAssignmentMode;
use App\Enums\InventoryTransactionType;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\DriverInventory;
use App\Models\DriverInventoryTransaction;
use App\Models\DriverStockAssignment;
use App\Models\Inventory;
use App\Models\User;
use App\Services\Inventory\InventoryService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DriverStockAssignmentService
{
    public function __construct(
        private InventoryService $inventory,
    ) {}

    /**
     * @param  list<int>  $driverIds
     * @param  list<array{product_id: int, variant_id?: int|null, quantity: int}>  $items
     */
    public function assign(
        Branch $branch,
        DriverStockAssignmentMode $mode,
        array $driverIds,
        array $items,
        ?User $actor = null,
    ): DriverStockAssignment {
        $driverIds = array_values(array_unique(array_map('intval', $driverIds)));
        $items = $this->normalizeItems($items);

        if ($driverIds === []) {
            throw ValidationException::withMessages([
                'driver_ids' => 'Select at least one driver.',
            ]);
        }

        if ($mode === DriverStockAssignmentMode::Single && count($driverIds) !== 1) {
            throw ValidationException::withMessages([
                'driver_ids' => 'Single mode requires exactly one driver.',
            ]);
        }

        if ($items === []) {
            throw ValidationException::withMessages([
                'items' => 'Add at least one product.',
            ]);
        }

        $drivers = User::query()
            ->whereIn('id', $driverIds)
            ->where('user_type', UserType::DRIVER)
            ->where('is_active', true)
            ->whereHas(
                'driverBranchAssignments',
                fn ($query) => $query
                    ->where('branch_id', $branch->id)
                    ->where('status', DriverAssignmentStatus::Active),
            )
            ->get();

        if ($drivers->count() !== count($driverIds)) {
            throw ValidationException::withMessages([
                'driver_ids' => 'One or more drivers are not assigned to this branch.',
            ]);
        }

        $driverCount = $drivers->count();

        try {
            return DB::transaction(function () use (
                $branch,
                $mode,
                $drivers,
                $items,
                $actor,
                $driverCount,
            ): DriverStockAssignment {
                $assignment = DriverStockAssignment::query()->create([
                    'branch_id' => $branch->id,
                    'assigned_by' => $actor?->id,
                    'mode' => $mode,
                ]);

                $assignment->drivers()->attach($drivers->pluck('id')->all());

                foreach ($items as $item) {
                    $assignment->items()->create([
                        'product_id' => $item['product_id'],
                        'variant_id' => $item['variant_id'],
                        'quantity' => $item['quantity'],
                    ]);

                    $inventory = $this->lockBranchInventory(
                        $branch->id,
                        $item['product_id'],
                        $item['variant_id'],
                    );

                    $available = $inventory->quantity - $inventory->reserved_quantity;
                    $required = $item['quantity'] * $driverCount;

                    if ($available < $required) {
                        throw ValidationException::withMessages([
                            'items' => "Insufficient stock for product #{$item['product_id']}. Need {$required}, available {$available}.",
                        ]);
                    }

                    $this->inventory->adjust(
                        $inventory,
                        InventoryTransactionType::DriverAssign,
                        $required,
                        $actor,
                        DriverStockAssignment::class,
                        $assignment->id,
                    );

                    foreach ($drivers->sortBy('id')->values() as $driver) {
                        $this->incrementDriverInventory(
                            $driver->id,
                            $branch->id,
                            $item['product_id'],
                            $item['variant_id'],
                            $item['quantity'],
                            $assignment,
                            $actor,
                        );
                    }
                }

                return $assignment->load(['drivers', 'items']);
            });
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'items' => $exception->getMessage(),
            ]);
        }
    }

    /**
     * @param  list<array{product_id: int|string, variant_id?: int|string|null, quantity: int|string}>  $items
     * @return list<array{product_id: int, variant_id: int|null, quantity: int}>
     */
    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['quantity'] ?? 0);
            $variantId = array_key_exists('variant_id', $item) && $item['variant_id'] !== null && $item['variant_id'] !== ''
                ? (int) $item['variant_id']
                : null;

            if ($productId < 1 || $quantity < 1) {
                continue;
            }

            $key = $productId.'|'.($variantId ?? 'null');

            if (isset($normalized[$key])) {
                $normalized[$key]['quantity'] += $quantity;
            } else {
                $normalized[$key] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'quantity' => $quantity,
                ];
            }
        }

        return array_values($normalized);
    }

    private function lockBranchInventory(int $branchId, int $productId, ?int $variantId): Inventory
    {
        $query = Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', true)
            ->lockForUpdate();

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        $inventory = $query->first();

        if ($inventory === null) {
            throw ValidationException::withMessages([
                'items' => "No branch stock found for product #{$productId}.",
            ]);
        }

        return $inventory;
    }

    private function incrementDriverInventory(
        int $userId,
        int $branchId,
        int $productId,
        ?int $variantId,
        int $quantity,
        DriverStockAssignment $assignment,
        ?User $actor,
    ): void {
        $query = DriverInventory::query()
            ->where('user_id', $userId)
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->lockForUpdate();

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        $row = $query->first();
        $before = $row?->quantity ?? 0;
        $after = $before + $quantity;

        if ($row === null) {
            DriverInventory::query()->create([
                'user_id' => $userId,
                'branch_id' => $branchId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'quantity' => $quantity,
            ]);
        } else {
            $row->quantity = $after;
            $row->save();
        }

        DriverInventoryTransaction::query()->create([
            'branch_id' => $branchId,
            'user_id' => $userId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'type' => InventoryTransactionType::DriverAssign,
            'reason' => null,
            'reason_note' => null,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $after,
            'reference_type' => DriverStockAssignment::class,
            'reference_id' => $assignment->id,
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
