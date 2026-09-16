<?php

namespace App\Services\Inventory;

use App\Enums\InventoryAdjustmentReason;
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
     * @param  array{status?: bool}  $attributes
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
            'status' => $attributes['status'] ?? true,
        ]);
    }

    public function adjust(
        Inventory $inventory,
        InventoryTransactionType $type,
        int $quantity,
        ?User $actor = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?InventoryAdjustmentReason $reason = null,
        ?string $reasonNote = null,
    ): Inventory {
        return DB::transaction(function () use ($inventory, $type, $quantity, $actor, $referenceType, $referenceId, $reason, $reasonNote): Inventory {
            /** @var Inventory $locked */
            $locked = Inventory::query()->whereKey($inventory->id)->lockForUpdate()->firstOrFail();

            $before = $locked->quantity;
            [$after, $storedQuantity, $storedReason, $storedNote] = $this->resolveChange(
                $type,
                $before,
                $quantity,
                $reason,
                $reasonNote,
            );

            if ($after < 0) {
                throw new InvalidArgumentException('Insufficient stock for this adjustment.');
            }

            $locked->quantity = $after;
            $locked->save();

            InventoryTransaction::query()->create([
                'branch_id' => $locked->branch_id,
                'product_id' => $locked->product_id,
                'variant_id' => $locked->variant_id,
                'type' => $type,
                'reason' => $storedReason,
                'reason_note' => $storedNote,
                'quantity' => $storedQuantity,
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

    /**
     * @return array{0: int, 1: int, 2: InventoryAdjustmentReason|null, 3: string|null}
     */
    private function resolveChange(
        InventoryTransactionType $type,
        int $before,
        int $quantity,
        ?InventoryAdjustmentReason $reason,
        ?string $reasonNote,
    ): array {
        if ($type === InventoryTransactionType::Adjustment) {
            if ($reason === null) {
                throw new InvalidArgumentException('An adjustment reason is required.');
            }

            if ($quantity < 0) {
                throw new InvalidArgumentException('Counted quantity cannot be negative.');
            }

            $after = $quantity;
            $variance = $after - $before;

            if ($variance === 0) {
                throw new InvalidArgumentException('Counted quantity matches current stock. No adjustment needed.');
            }

            return [$after, $variance, $reason, $this->storedReasonNote($reason, $reasonNote)];
        }

        if ($reason !== null || InventoryAdjustmentReason::noteFrom($reasonNote) !== null) {
            throw new InvalidArgumentException('A reason is only used for adjustments.');
        }

        $delta = abs($quantity);

        if ($delta === 0) {
            throw new InvalidArgumentException('Quantity delta cannot be zero.');
        }

        $after = match ($type) {
            InventoryTransactionType::StockIn,
            InventoryTransactionType::Release => $before + $delta,
            default => $before - $delta,
        };

        return [$after, $delta, null, null];
    }

    private function storedReasonNote(InventoryAdjustmentReason $reason, ?string $reasonNote): ?string
    {
        $note = InventoryAdjustmentReason::noteFrom($reasonNote);

        if ($reason->requiresNote()) {
            if ($note === null) {
                throw new InvalidArgumentException('Describe the adjustment reason.');
            }

            return $note;
        }

        if ($note !== null) {
            throw new InvalidArgumentException('A custom reason is only used when Other is selected.');
        }

        return null;
    }
}
