<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;

final class ProductBranchInventoryPayload
{
    /**
     * @return array{
     *     id: int,
     *     name: string,
     *     slug: string,
     *     sku: ?string,
     *     image_url: ?string,
     *     color_code: mixed,
     *     category_name: string,
     *     status: bool
     * }
     */
    public static function product(Product $product): array
    {
        $product->loadMissing(['categories:id,name,slug']);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image_url' => $product->imageUrl(),
            'color_code' => $product->color_code,
            'category_name' => $product->categoryNamesLabel(),
            'status' => (bool) $product->status,
        ];
    }

    /**
     * Network-wide summary for a product (independent of the DataTable page).
     *
     * @return array{branches: int, enabled: int, low_stock: int, missing_price: int}
     */
    public static function summary(Product $product): array
    {
        $branchCount = Branch::query()->count();

        $enabled = BranchProduct::query()
            ->where('product_id', $product->id)
            ->where('status', true)
            ->count();

        $lowStock = Inventory::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->whereColumn('available_quantity', '<=', 'minimum_stock')
            ->count();

        $pricedBranches = BranchProductPrice::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->distinct()
            ->count('branch_id');

        return [
            'branches' => $branchCount,
            'enabled' => $enabled,
            'low_stock' => $lowStock,
            'missing_price' => max(0, $branchCount - $pricedBranches),
        ];
    }
}
