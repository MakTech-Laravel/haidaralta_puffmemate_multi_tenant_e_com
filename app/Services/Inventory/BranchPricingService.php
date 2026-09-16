<?php

namespace App\Services\Inventory;

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BranchPricingService
{
    /**
     * @param  array{
     *     selling_price: float|string,
     *     discount_type?: string|DiscountType|null,
     *     discount_price?: float|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     variant_id?: int|null
     * }  $data
     */
    public function upsert(
        Branch $branch,
        Product $product,
        array $data,
        ?ProductVariant $variant = null,
    ): BranchProductPrice {
        return DB::transaction(function () use ($branch, $product, $data, $variant): BranchProductPrice {
            $variantId = $variant?->id ?? ($data['variant_id'] ?? null);

            $query = BranchProductPrice::query()
                ->where('branch_id', $branch->id)
                ->where('product_id', $product->id)
                ->where('status', true);

            if ($variantId === null) {
                $query->whereNull('variant_id');
            } else {
                $query->where('variant_id', $variantId);
            }

            $price = $query->first() ?? new BranchProductPrice([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
                'variant_id' => $variantId,
            ]);

            $discountType = $this->resolveDiscountType($data['discount_type'] ?? null, $data['discount_price'] ?? null);
            $hasDiscount = $discountType !== DiscountType::None;

            $price->fill([
                'selling_price' => $data['selling_price'],
                'discount_type' => $discountType,
                'discount_price' => $hasDiscount ? ($data['discount_price'] ?? null) : null,
                'start_date' => $hasDiscount ? ($data['start_date'] ?? null) : null,
                'end_date' => $hasDiscount ? ($data['end_date'] ?? null) : null,
                'status' => true,
            ]);
            $price->save();

            return $price->refresh();
        });
    }

    /**
     * @param  array{
     *     discount_type: string|DiscountType,
     *     discount_price?: float|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null
     * }  $data
     */
    public function applyDiscount(Branch $branch, Product $product, array $data): BranchProductPrice
    {
        $existing = $this->activePrice($branch, $product);

        if ($existing === null) {
            throw new \InvalidArgumentException('Set a selling price before adding a discount.');
        }

        return $this->upsert($branch, $product, [
            'selling_price' => $existing->selling_price,
            'discount_type' => $data['discount_type'],
            'discount_price' => $data['discount_price'] ?? null,
            'start_date' => $data['start_date'] ?? null,
            'end_date' => $data['end_date'] ?? null,
        ]);
    }

    /**
     * @param  list<int>  $branchIds
     * @param  list<int>  $productIds
     * @return array{pairs: int, with_offer: int, active: int, missing_price: int}
     */
    public function previewSelectedPairs(array $branchIds, array $productIds): array
    {
        $branchIds = array_values(array_unique(array_filter($branchIds, fn (int $id): bool => $id > 0)));
        $productIds = array_values(array_unique(array_filter($productIds, fn (int $id): bool => $id > 0)));
        $pairs = count($branchIds) * count($productIds);

        if ($pairs === 0) {
            return [
                'pairs' => 0,
                'with_offer' => 0,
                'active' => 0,
                'missing_price' => 0,
            ];
        }

        $prices = $this->pricesForPairs($branchIds, $productIds);
        $withOffer = 0;
        $active = 0;

        foreach ($branchIds as $branchId) {
            foreach ($productIds as $productId) {
                $price = $prices->get($this->pairKey($branchId, $productId));

                if ($price === null || $price->discount_type === DiscountType::None) {
                    continue;
                }

                $withOffer++;

                if ($this->offerIsCurrentlyActive($price)) {
                    $active++;
                }
            }
        }

        return [
            'pairs' => $pairs,
            'with_offer' => $withOffer,
            'active' => $active,
            'missing_price' => $pairs - $prices->count(),
        ];
    }

    /**
     * @param  list<int>  $branchIds
     * @param  list<int>  $productIds
     * @return Collection<string, BranchProductPrice>
     */
    public function pricesForPairs(array $branchIds, array $productIds): Collection
    {
        return BranchProductPrice::query()
            ->whereIn('branch_id', $branchIds)
            ->whereIn('product_id', $productIds)
            ->whereNull('variant_id')
            ->where('status', true)
            ->get()
            ->keyBy(fn (BranchProductPrice $price): string => $this->pairKey(
                (int) $price->branch_id,
                (int) $price->product_id,
            ));
    }

    public function offerIsCurrentlyActive(BranchProductPrice $price): bool
    {
        if ($price->discount_type === DiscountType::None) {
            return false;
        }

        $today = now()->startOfDay();

        if ($price->start_date !== null && $today->lt($price->start_date->copy()->startOfDay())) {
            return false;
        }

        if ($price->end_date !== null && $today->gt($price->end_date->copy()->endOfDay())) {
            return false;
        }

        return true;
    }

    public function activePrice(Branch $branch, Product $product): ?BranchProductPrice
    {
        return BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->whereNull('variant_id')
            ->where('status', true)
            ->first();
    }

    private function pairKey(int $branchId, int $productId): string
    {
        return $branchId.':'.$productId;
    }

    private function resolveDiscountType(mixed $type, mixed $discountPrice): DiscountType
    {
        if ($type instanceof DiscountType) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            return DiscountType::from($type);
        }

        return $discountPrice !== null && $discountPrice !== ''
            ? DiscountType::Fixed
            : DiscountType::None;
    }
}
