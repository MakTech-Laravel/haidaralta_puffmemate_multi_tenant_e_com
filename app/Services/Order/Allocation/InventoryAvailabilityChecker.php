<?php

namespace App\Services\Order\Allocation;

use App\Models\Inventory;

/**
 * Batch-loads sellable stock (quantity - reserved_quantity) for every branch
 * that stocks any of the requested products, in a single query. Never caches
 * this data — inventory accuracy matters more than shaving off one query.
 */
class InventoryAvailabilityChecker
{
    /**
     * @param  list<array{product_id: int, variant_id: int|null, quantity: int}>  $lines
     * @return array<int, array<string, int>> stock[branch_id][sku] = sellable quantity
     */
    public function sellableByBranch(array $lines): array
    {
        $productIds = array_values(array_unique(array_map(fn (array $line): int => $line['product_id'], $lines)));

        $stock = [];

        $rows = Inventory::query()
            ->whereIn('product_id', $productIds)
            ->where('status', true)
            ->get();

        foreach ($rows as $row) {
            $sku = self::skuKey($row->product_id, $row->variant_id);
            $stock[$row->branch_id][$sku] = max(0, $row->quantity - $row->reserved_quantity);
        }

        return $stock;
    }

    public static function skuKey(int $productId, ?int $variantId): string
    {
        return $productId.':'.($variantId ?? '0');
    }
}
