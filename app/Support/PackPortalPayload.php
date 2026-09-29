<?php

namespace App\Support;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;

/**
 * Pack payloads shared by the branch and platform Packs & Saves portals.
 */
class PackPortalPayload
{
    /**
     * Catalog-active SKUs that already have a stock row at this branch.
     *
     * @return list<array{
     *     id: int,
     *     name: string,
     *     sku: string|null,
     *     image_url: string|null,
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     quantity: int
     * }>
     */
    public static function availableProducts(Branch $branch): array
    {
        $inventories = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereNull('variant_id')
            ->orderBy('product_id')
            ->pluck('quantity', 'product_id');

        if ($inventories->isEmpty()) {
            return [];
        }

        $products = Product::query()
            ->whereIn('id', $inventories->keys())
            ->where('status', true)
            ->orderBy('name')
            ->orderBy('id')
            ->get();

        $prices = collect();

        if (BranchProductPriceDisplay::usesBranchPricing() && $products->isNotEmpty()) {
            $prices = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $products->modelKeys())
                ->whereNull('variant_id')
                ->where('status', true)
                ->get()
                ->keyBy('product_id');
        }

        return $products
            ->map(function (Product $product) use ($prices, $inventories): array {
                /** @var BranchProductPrice|null $price */
                $price = $prices->get($product->id);
                $display = BranchProductPriceDisplay::listDisplayForProductContext(
                    $price,
                    $product,
                );

                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                    'image_url' => $product->imageUrl(),
                    'selling_price' => $display['selling_price'],
                    'effective_price' => $display['effective_price'],
                    'quantity' => (int) $inventories->get($product->id, 0),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function pack(Branch $branch, Pack $pack, bool $detailed = false): array
    {
        $payload = $pack->toPortal($detailed);

        if (! $detailed) {
            return $payload;
        }

        $productIds = $pack->items->pluck('product_id');
        $remaining = Inventory::query()
            ->where('branch_id', $branch->id)
            ->whereIn('product_id', $productIds)
            ->whereNull('variant_id')
            ->pluck('quantity', 'product_id');

        $prices = collect();

        if (BranchProductPriceDisplay::usesBranchPricing() && $productIds->isNotEmpty()) {
            $prices = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->whereIn('product_id', $productIds)
                ->whereNull('variant_id')
                ->where('status', true)
                ->get()
                ->keyBy('product_id');
        }

        $payload['items'] = $pack->items
            ->map(function (PackItem $item) use ($prices, $remaining): array {
                $product = $item->product;
                $display = BranchProductPriceDisplay::listDisplayForProductContext(
                    $prices->get($item->product_id),
                    $product,
                );

                return [
                    'product_id' => $item->product_id,
                    'quantity' => $item->quantity,
                    'product' => [
                        'id' => $product?->id,
                        'name' => $product?->name ?? 'Unknown product',
                        'sku' => $product?->sku,
                        'image_url' => $product?->imageUrl(),
                        'selling_price' => $display['selling_price'],
                        'effective_price' => $display['effective_price'],
                        'quantity' => (int) $remaining->get($item->product_id, 0),
                    ],
                ];
            })
            ->values()
            ->all();

        return $payload;
    }
}
