<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\InventoryTransaction;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

final class BranchProductShowPayload
{
    /**
     * Shared branch/platform inventory show product payload (no transactions).
     *
     * @return array<string, mixed>
     */
    public static function product(Branch $branch, Product $product): array
    {
        $product->loadMissing(['categories:id,name,slug', 'variants', 'media', 'creator:id,name']);

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

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'description' => $product->description,
            'brand' => $product->brand,
            'pack_label' => $product->pack_label,
            'pieces_per_pack' => $product->pieces_per_pack,
            'pack_unit' => $product->pack_unit,
            'pack_summary' => $product->packSummary(),
            'tags' => $product->tags ?? [],
            'specifications' => ProductSpecifications::normalize($product->specifications),
            'status' => (bool) $product->status,
            'created_at' => $product->created_at?->toIso8601String(),
            'updated_at' => $product->updated_at?->toIso8601String(),
            'created_by_name' => $product->creator?->name,
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
            'gallery' => CatalogMediaPayload::gallery($product),
            'variants' => $product->variants->map(fn (ProductVariant $variant): array => [
                'id' => $variant->id,
                'name' => $variant->name,
                'sku' => $variant->sku,
                'status' => (bool) $variant->status,
            ])->values()->all(),
            'inventory' => $baseInventory ? self::mapInventory($baseInventory) : null,
            'price' => $basePrice ? self::mapPrice($basePrice) : null,
            'variant_inventories' => $variantInventories,
            'variant_prices' => $variantPrices,
        ];
    }

    /**
     * Paginated inventory transactions for the product show page.
     *
     * Query params: txn_scope (base|{variant_id}), txn_from, txn_to, txn_type, txn_page.
     *
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array{scope: string, from: ?string, to: ?string, type: ?string}
     * }
     */
    public static function transactions(Branch $branch, Product $product, Request $request): array
    {
        $scope = $request->string('txn_scope')->toString() ?: 'base';
        $from = $request->date('txn_from');
        $to = $request->date('txn_to');
        $type = InventoryTransactionType::tryFrom($request->string('txn_type')->toString());

        $query = InventoryTransaction::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->latest('created_at')
            ->latest('id');

        if ($scope === 'base') {
            $query->whereNull('variant_id');
        } else {
            $query->where('variant_id', (int) $scope);
        }

        if ($from !== null) {
            $query->whereDate('created_at', '>=', $from);
        }

        if ($to !== null) {
            $query->whereDate('created_at', '<=', $to);
        }

        if ($type !== null) {
            $query->where('type', $type);
        }

        /** @var LengthAwarePaginator<int, InventoryTransaction> $paginator */
        $paginator = $query
            ->paginate(15, ['*'], 'txn_page')
            ->withQueryString()
            ->through(fn (InventoryTransaction $txn): array => [
                'id' => $txn->id,
                'type' => $txn->type->value,
                'type_label' => $txn->type->label(),
                'reason' => $txn->reason?->value,
                'reason_note' => $txn->reason_note,
                'reason_label' => $txn->reasonLabel(),
                'quantity' => $txn->quantity,
                'before_quantity' => $txn->before_quantity,
                'after_quantity' => $txn->after_quantity,
                'variant_id' => $txn->variant_id,
                'created_at' => $txn->created_at?->toIso8601String(),
            ]);

        return [
            'data' => array_values($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'from' => $paginator->firstItem(),
                'to' => $paginator->lastItem(),
            ],
            'links' => collect($paginator->toArray()['links'] ?? [])->map(fn (array $link): array => [
                'url' => $link['url'] ?? null,
                'label' => (string) ($link['label'] ?? ''),
                'active' => (bool) ($link['active'] ?? false),
            ])->values()->all(),
            'filters' => [
                'scope' => $scope,
                'from' => $from?->toDateString(),
                'to' => $to?->toDateString(),
                'type' => $type?->value,
            ],
        ];
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function transactionTypes(): array
    {
        return InventoryTransactionType::manualOptions();
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function discountTypes(): array
    {
        return [
            ['value' => DiscountType::None->value, 'label' => 'None'],
            ['value' => DiscountType::Fixed->value, 'label' => 'Amount off'],
            ['value' => DiscountType::Percent->value, 'label' => 'Percent'],
        ];
    }

    /**
     * @return array{quantity: int}
     */
    private static function mapInventory(Inventory $inventory): array
    {
        return [
            'quantity' => $inventory->quantity,
        ];
    }

    /**
     * @return array{
     *     buying_price: mixed,
     *     regular_price: mixed,
     *     selling_price: mixed,
     *     discount_type: string,
     *     discount_price: mixed,
     *     start_date: ?string,
     *     end_date: ?string,
     *     status: bool
     * }
     */
    private static function mapPrice(BranchProductPrice $price): array
    {
        return [
            'buying_price' => $price->buying_price,
            'regular_price' => $price->regular_price,
            'selling_price' => $price->selling_price,
            'discount_type' => $price->discount_type?->value ?? DiscountType::None->value,
            'discount_price' => $price->discount_price,
            'start_date' => $price->start_date?->toDateString(),
            'end_date' => $price->end_date?->toDateString(),
            'status' => (bool) $price->status,
        ];
    }
}
