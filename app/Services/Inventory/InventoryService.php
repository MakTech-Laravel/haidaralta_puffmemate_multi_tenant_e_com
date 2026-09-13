<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class InventoryService
{
    /**
     * @param  array{minimum_stock?: int, status?: bool}  $attributes
     */
    public function firstOrCreate(
        Branch $branch,
        Product $product,
        ?ProductVariant $variant = null,
        array $attributes = [],
    ): Inventory {
        $query = Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id);

        if ($variant === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variant->id);
        }

        $existing = $query->first();

        if ($existing !== null) {
            return $existing;
        }

        return Inventory::query()->create([
            'branch_id' => $branch->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'available_quantity' => 0,
            'minimum_stock' => $attributes['minimum_stock'] ?? 0,
            'status' => $attributes['status'] ?? true,
        ]);
    }

    public function adjust(
        Inventory $inventory,
        InventoryTransactionType $type,
        int $quantityDelta,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
    ): Inventory {
        if ($quantityDelta === 0 && $type !== InventoryTransactionType::Adjustment) {
            throw new InvalidArgumentException('Quantity delta cannot be zero.');
        }

        return DB::transaction(function () use ($inventory, $type, $quantityDelta, $actor, $referenceType, $referenceId): Inventory {
            /** @var Inventory $locked */
            $locked = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

            $before = $locked->quantity;
            $after = match ($type) {
                InventoryTransactionType::StockIn,
                InventoryTransactionType::Release => $before + abs($quantityDelta),
                InventoryTransactionType::StockOut,
                InventoryTransactionType::Sale,
                InventoryTransactionType::Reserve => $before - abs($quantityDelta),
                InventoryTransactionType::Adjustment => $before + $quantityDelta,
            };

            if ($after < 0) {
                throw new InvalidArgumentException('Insufficient stock for this adjustment.');
            }

            $reserved = $locked->reserved_quantity;

            if ($type === InventoryTransactionType::Reserve) {
                $reserved += abs($quantityDelta);
            }

            if ($type === InventoryTransactionType::Release) {
                $reserved = max(0, $reserved - abs($quantityDelta));
            }

            if ($reserved > $after) {
                throw new InvalidArgumentException('Reserved quantity cannot exceed on-hand quantity.');
            }

            $locked->quantity = $after;
            $locked->reserved_quantity = $reserved;
            $locked->available_quantity = $after - $reserved;
            $locked->save();

            InventoryTransaction::query()->create([
                'branch_id' => $locked->branch_id,
                'product_id' => $locked->product_id,
                'variant_id' => $locked->variant_id,
                'type' => $type,
                'quantity' => $quantityDelta,
                'before_quantity' => $before,
                'after_quantity' => $after,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'created_by' => $actor?->id,
                'created_at' => now(),
            ]);

            return $locked->refresh();
        });
    }

    public function setMinimumStock(Inventory $inventory, int $minimumStock): Inventory
    {
        $inventory->minimum_stock = max(0, $minimumStock);
        $inventory->save();

        return $inventory->refresh();
    }
}
