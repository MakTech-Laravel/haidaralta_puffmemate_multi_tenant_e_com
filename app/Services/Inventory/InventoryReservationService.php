<?php

namespace App\Services\Inventory;

use App\Enums\InventoryTransactionType;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class InventoryReservationService
{
    public function available(Inventory $inventory): int
    {
        return max(0, $inventory->quantity - $inventory->reserved_quantity);
    }

    /**
     * @param  list<int>  $ids
     * @return Collection<int, Inventory>
     */
    public function lockOrdered(array $ids): Collection
    {
        $ids = array_values(array_unique(array_filter($ids)));
        sort($ids);

        if ($ids === []) {
            return new Collection;
        }

        return Inventory::query()
            ->whereIn('id', $ids)
            ->orderBy('id')
            ->lockForUpdate()
            ->get()
            ->keyBy('id');
    }

    public function reserve(Inventory $locked, int $quantity, Model $reference, ?User $actor = null): Inventory
    {
        $this->assertPositive($quantity);

        if ($this->available($locked) < $quantity) {
            throw ValidationException::withMessages([
                'cart' => 'One or more items are no longer available.',
            ]);
        }

        $locked->reserved_quantity += $quantity;
        $locked->save();

        $this->ledger($locked, InventoryTransactionType::Reserve, $quantity, $reference, $actor);

        return $locked;
    }

    public function release(Inventory $locked, int $quantity, Model $reference, ?User $actor = null): Inventory
    {
        $this->assertPositive($quantity);

        if ($locked->reserved_quantity < $quantity) {
            throw ValidationException::withMessages([
                'cart' => 'Reserved stock cannot be released.',
            ]);
        }

        $locked->reserved_quantity -= $quantity;
        $locked->save();

        $this->ledger($locked, InventoryTransactionType::Release, $quantity, $reference, $actor);

        return $locked;
    }

    public function sale(Inventory $locked, int $quantity, Model $reference, ?User $actor = null): Inventory
    {
        $this->assertPositive($quantity);

        if ($locked->reserved_quantity < $quantity || $locked->quantity < $quantity) {
            throw ValidationException::withMessages([
                'cart' => 'Reserved stock cannot be sold.',
            ]);
        }

        $before = $locked->quantity;
        $locked->quantity -= $quantity;
        $locked->reserved_quantity -= $quantity;
        $locked->save();

        $this->ledger($locked, InventoryTransactionType::Sale, $quantity, $reference, $actor, $before);

        return $locked;
    }

    public function transferOut(Inventory $locked, int $quantity, Model $reference, ?User $actor = null): Inventory
    {
        $this->assertPositive($quantity);

        if ($locked->reserved_quantity < $quantity || $locked->quantity < $quantity) {
            throw ValidationException::withMessages([
                'cart' => 'Reserved stock cannot be transferred.',
            ]);
        }

        $before = $locked->quantity;
        $locked->quantity -= $quantity;
        $locked->reserved_quantity -= $quantity;
        $locked->save();

        $this->ledger($locked, InventoryTransactionType::TransferOut, $quantity, $reference, $actor, $before);

        return $locked;
    }

    public function transferIn(Inventory $locked, int $quantity, Model $reference, ?User $actor = null): Inventory
    {
        $this->assertPositive($quantity);

        $before = $locked->quantity;
        $locked->quantity += $quantity;
        $locked->reserved_quantity += $quantity;
        $locked->save();

        $this->ledger($locked, InventoryTransactionType::TransferIn, $quantity, $reference, $actor, $before);

        return $locked;
    }

    public function firstOrCreateLocked(int $branchId, int $productId, ?int $variantId): Inventory
    {
        $query = Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId);

        if ($variantId === null) {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', $variantId);
        }

        $existing = $query->lockForUpdate()->first();

        if ($existing !== null) {
            return $existing;
        }

        return Inventory::query()->create([
            'branch_id' => $branchId,
            'product_id' => $productId,
            'variant_id' => $variantId,
            'quantity' => 0,
            'reserved_quantity' => 0,
            'status' => true,
        ]);
    }

    private function assertPositive(int $quantity): void
    {
        if ($quantity <= 0) {
            throw ValidationException::withMessages([
                'cart' => 'Quantity must be greater than zero.',
            ]);
        }
    }

    private function ledger(
        Inventory $inventory,
        InventoryTransactionType $type,
        int $quantity,
        Model $reference,
        ?User $actor,
        ?int $beforeQuantity = null,
    ): void {
        $before = $beforeQuantity ?? $inventory->quantity;

        InventoryTransaction::query()->create([
            'branch_id' => $inventory->branch_id,
            'product_id' => $inventory->product_id,
            'variant_id' => $inventory->variant_id,
            'type' => $type,
            'quantity' => $quantity,
            'before_quantity' => $before,
            'after_quantity' => $inventory->quantity,
            'reference_type' => $reference->getMorphClass(),
            'reference_id' => $reference->getKey(),
            'created_by' => $actor?->id,
            'created_at' => now(),
        ]);
    }
}
