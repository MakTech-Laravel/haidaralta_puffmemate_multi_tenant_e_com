<?php

namespace App\Support;

use App\Models\Inventory;
use App\Models\Pack;
use App\Models\PackItem;

class PackAvailability
{
    public function remainingOnHand(int $branchId, int $productId): int
    {
        return (int) Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->whereNull('variant_id')
            ->value('quantity');
    }

    public function syncForProduct(int $branchId, int $productId): void
    {
        $remaining = (int) Inventory::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->whereNull('variant_id')
            ->value('quantity');

        $packs = Pack::query()
            ->where('branch_id', $branchId)
            ->where('status', true)
            ->whereHas('items', fn ($query) => $query->where('product_id', $productId))
            ->with(['items' => fn ($query) => $query->where('product_id', $productId)])
            ->orderBy('id')
            ->get();

        foreach ($packs as $pack) {
            /** @var PackItem|null $item */
            $item = $pack->items->first();
            $lineQty = (int) ($item?->quantity ?? 0);

            if ($pack->quantity === 0 || $lineQty < 1 || $remaining < $lineQty) {
                $pack->update(['status' => false]);
            }
        }
    }

    public function canActivate(Pack $pack): bool
    {
        if ($pack->quantity < 1) {
            return false;
        }

        $pack->loadMissing('items.product');

        foreach ($pack->items as $item) {
            $product = $item->product;

            if ($product === null) {
                return false;
            }

            if ($this->remainingOnHand($pack->branch_id, $product->id) < $item->quantity) {
                return false;
            }
        }

        return true;
    }
}
