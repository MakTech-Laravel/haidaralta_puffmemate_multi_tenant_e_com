<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use Illuminate\Support\Collection;

class BranchProductPriceDisplay
{
    /**
     * @return array{
     *     amount: float|null,
     *     selling: float|null,
     *     effective: float|null,
     *     has_discount: bool,
     *     label: string|null,
     *     is_from: bool,
     *     count: int
     * }
     */
    public static function forBranchProduct(Branch $branch, Product $product): array
    {
        $amounts = self::activeAmounts($branch->id, $product->id);

        if ($amounts->isEmpty()) {
            return [
                'amount' => null,
                'selling' => null,
                'effective' => null,
                'has_discount' => false,
                'label' => null,
                'is_from' => false,
                'count' => 0,
            ];
        }

        $min = (float) $amounts->min();
        $isFrom = $amounts->count() > 1;

        return [
            'amount' => $min,
            'selling' => $min,
            'effective' => $min,
            'has_discount' => false,
            'label' => $isFrom
                ? 'Start from $'.number_format($min, 2)
                : '$'.number_format($min, 2),
            'is_from' => $isFrom,
            'count' => $amounts->count(),
        ];
    }

    /**
     * @return array{
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     has_discount: bool,
     *     discount_type: string,
     *     discount_price: string|null
     * }
     */
    public static function listDisplay(?BranchProductPrice $price): array
    {
        if ($price === null) {
            return [
                'selling_price' => null,
                'effective_price' => null,
                'has_discount' => false,
                'discount_type' => DiscountType::None->value,
                'discount_price' => null,
            ];
        }

        $selling = (float) $price->selling_price;
        $effective = self::effectiveAmount($price);
        $hasDiscount = $price->discount_type !== DiscountType::None && $effective < $selling;

        return [
            'selling_price' => number_format($selling, 2, '.', ''),
            'effective_price' => number_format($effective, 2, '.', ''),
            'has_discount' => $hasDiscount,
            'discount_type' => $price->discount_type->value,
            'discount_price' => $price->discount_price === null
                ? null
                : (string) $price->discount_price,
        ];
    }

    /**
     * @return Collection<int, float>
     */
    private static function activeAmounts(int $branchId, int $productId): Collection
    {
        return BranchProductPrice::query()
            ->where('branch_id', $branchId)
            ->where('product_id', $productId)
            ->where('status', true)
            ->get()
            ->map(fn (BranchProductPrice $price): float => self::effectiveAmount($price))
            ->values();
    }

    public static function effectiveAmount(BranchProductPrice $price): float
    {
        $selling = (float) $price->selling_price;

        return match ($price->discount_type) {
            DiscountType::Fixed => max(0, (float) $price->discount_price),
            DiscountType::Percent => max(
                0,
                round($selling * (1 - ((float) $price->discount_price / 100)), 2),
            ),
            default => $selling,
        };
    }
}
