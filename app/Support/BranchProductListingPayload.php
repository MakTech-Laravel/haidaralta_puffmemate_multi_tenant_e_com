<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;

final class BranchProductListingPayload
{
    /**
     * Branch-wide inventory summary (independent of the DataTable page).
     *
     * @return array{products: int, missing_price: int}
     */
    public static function summary(Branch $branch): array
    {
        $productCount = Product::query()->count();

        $priced = BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->distinct()
            ->count('product_id');

        return [
            'products' => $productCount,
            'missing_price' => max(0, $productCount - $priced),
        ];
    }
}
