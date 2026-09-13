<?php

namespace App\Support;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;

final class BranchProductShowPayload
{
    /**
     * Shared branch/platform inventory show product payload.
     *
     * @return array<string, mixed>
     */
    public static function product(Branch $branch, Product $product, bool $enabled): array
    {
        $product->loadMissing(['categories:id,name,slug', 'variants']);

        $inventories = Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->get();

        $prices = BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('status', true)
            ->get();

        /** @var Inventory|null $baseInventory */
        $baseInventory = $inventories->first(fn (Inventory $row): bool => $row->variant_id === null);
        /** @var BranchProductPrice|null $basePrice */
        $basePrice = $prices->first(fn (BranchProductPrice $row): bool => $row->variant_id === null);

        $variantInventories = [];
        $variantPrices = [];

        foreach ($inventories as $inventory) {
            if ($inventory->variant_id === null) {
                continue;
            }

            $variantInventories[(string) $inventory->variant_id] = self::mapInventory($inventory);
        }

        foreach ($prices as $price) {
            if ($price->variant_id === null) {
                continue;
            }

            $variantPrices[(string) $price->variant_id] = self::mapPrice($price);
        }

        $transactions = InventoryTransaction::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->latest('created_at')
            ->limit(25)
            ->get()
            ->map(fn (InventoryTransaction $txn): array => [
                'id' => $txn->id,
                'type' => $txn->type->value,
                'quantity' => $txn->quantity,
                'before_quantity' => $txn->before_quantity,
                'after_quantity' => $txn->after_quantity,
                'variant_id' => $txn->variant_id,
                'created_at' => $txn->created_at?->toIso8601String(),
            ])
            ->values()
            ->all();

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'status' => (bool) $product->status,
            'image_url' => $product->imageUrl(),
            'color_code' => $product->color_code,
            'category_ids' => $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'categories' => $product->categories
                ->map(fn (Category $category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'category_name' => $product->categoryNamesLabel(),
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'status' => (bool) $variant->status,
            ])->values()->all(),
            'enabled' => $enabled,
            'inventory' => $baseInventory ? self::mapInventory($baseInventory) : null,
            'price' => $basePrice ? self::mapPrice($basePrice) : null,
            'variant_inventories' => $variantInventories,
            'variant_prices' => $variantPrices,
            'transactions' => $transactions,
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function transactionTypes(): array
    {
        return collect(InventoryTransactionType::cases())
            ->map(fn (InventoryTransactionType $type): array => [
                'value' => $type->value,
                'label' => str_replace('_', ' ', ucfirst($type->value)),
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     quantity: int,
     *     reserved_quantity: int,
     *     available_quantity: int,
     *     minimum_stock: int,
     *     is_low_stock: bool
     * }
     */
    private static function mapInventory(Inventory $inventory): array
    {
        return [
            'quantity' => $inventory->quantity,
            'reserved_quantity' => $inventory->reserved_quantity,
            'available_quantity' => $inventory->available_quantity,
            'minimum_stock' => $inventory->minimum_stock,
            'is_low_stock' => $inventory->isLowStock(),
        ];
    }

    /**
     * @return array{
     *     selling_price: mixed,
     *     discount_price: mixed,
     *     start_date: ?string,
     *     end_date: ?string,
     *     status: bool
     * }
     */
    private static function mapPrice(BranchProductPrice $price): array
    {
        return [
            'selling_price' => $price->selling_price,
            'discount_price' => $price->discount_price,
            'start_date' => $price->start_date?->toDateString(),
            'end_date' => $price->end_date?->toDateString(),
            'status' => (bool) $price->status,
        ];
    }
}
