<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;

final class BranchProductListingPayload
{
    /**
     * Branch-wide inventory summary (independent of the DataTable page).
     *
     * @return array{products: int, enabled: int, low_stock: int, missing_price: int}
     */
    public static function summary(Branch $branch): array
    {
        $productCount = Product::query()->count();

        $enabled = BranchProduct::query()
            ->where('branch_id', $branch->id)
            ->where('status', true)
            ->count();

        $lowStock = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereNull('variant_id')
            ->whereColumn('available_quantity', '<=', 'minimum_stock')
            ->count();

        $enabledProductIds = BranchProduct::query()
            ->where('branch_id', $branch->id)
            ->where('status', true)
            ->pluck('product_id');

        $pricedAmongEnabled = $enabledProductIds->isEmpty()
            ? 0
            : BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereNull('variant_id')
                ->where('status', true)
                ->whereIn('product_id', $enabledProductIds)
                ->distinct()
                ->count('product_id');

        return [
            'products' => $productCount,
            'enabled' => $enabled,
            'low_stock' => $lowStock,
            'missing_price' => max(0, $enabled - $pricedAmongEnabled),
        ];
    }
}
