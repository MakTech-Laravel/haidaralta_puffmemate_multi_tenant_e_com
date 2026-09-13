<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;

final class ProductBranchInventoryPayload
{
    /**
     * Platform catalog → per-branch inventory overview for one product.
     *
     * @return array{
     *     product: array<string, mixed>,
     *     summary: array{branches: int, enabled: int, low_stock: int, missing_price: int},
     *     branches: list<array<string, mixed>>
     * }
     */
    public static function make(Product $product): array
    {
        $product->loadMissing(['categories:id,name,slug']);

        $branches = Branch::query()
            ->orderBy('name')
            ->get(['id', 'name', 'slug', 'code', 'status']);

        $enabledByBranch = BranchProduct::query()
            ->where('product_id', $product->id)
            ->get(['branch_id', 'status'])
            ->keyBy('branch_id');

        $inventories = Inventory::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->get()
            ->keyBy('branch_id');

        $prices = BranchProductPrice::query()
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->get()
            ->keyBy('branch_id');

        $rows = $branches->map(function (Branch $branch) use ($product, $enabledByBranch, $inventories, $prices): array {
            /** @var BranchProduct|null $availability */
            $availability = $enabledByBranch->get($branch->id);
            $enabled = (bool) ($availability?->status ?? false);

            /** @var Inventory|null $inventory */
            $inventory = $inventories->get($branch->id);
            /** @var BranchProductPrice|null $price */
            $price = $prices->get($branch->id);

            $isLowStock = $inventory?->isLowStock() ?? false;

            return [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
                'code' => $branch->code,
                'branch_active' => (bool) $branch->status,
                'enabled' => $enabled,
                'inventory' => $inventory === null ? null : [
                    'quantity' => $inventory->quantity,
                    'available_quantity' => $inventory->available_quantity,
                    'minimum_stock' => $inventory->minimum_stock,
                    'is_low_stock' => $isLowStock,
                ],
                'price' => $price === null ? null : [
                    'selling_price' => (string) $price->selling_price,
                    'discount_type' => $price->discount_type?->value ?? DiscountType::None->value,
                    'discount_price' => $price->discount_price === null
                        ? null
                        : (string) $price->discount_price,
                ],
                'manage_url' => route('platform.branches.products.show', [
                    'branch' => $branch->slug,
                    'product' => $product->slug,
                ]),
            ];
        })->values()->all();

        $enabledCount = collect($rows)->where('enabled', true)->count();
        $lowStockCount = collect($rows)->filter(
            fn (array $row): bool => (bool) ($row['inventory']['is_low_stock'] ?? false),
        )->count();
        $missingPriceCount = collect($rows)->whereNull('price')->count();

        return [
            'product' => [
                'id' => $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'sku' => $product->sku,
                'image_url' => $product->imageUrl(),
                'color_code' => $product->color_code,
                'category_name' => $product->categoryNamesLabel(),
                'status' => (bool) $product->status,
            ],
            'summary' => [
                'branches' => count($rows),
                'enabled' => $enabledCount,
                'low_stock' => $lowStockCount,
                'missing_price' => $missingPriceCount,
            ],
            'branches' => $rows,
        ];
    }
}
