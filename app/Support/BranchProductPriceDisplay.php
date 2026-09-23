<?php

namespace App\Support;

use App\Enums\DiscountType;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;

class BranchProductPriceDisplay
{
    /**
     * @return array{
     *     amount: float|null,
     *     selling: float|null,
     *     effective: float|null,
     *     compare_at: float|null,
     *     has_discount: bool,
     *     label: string|null,
     *     is_from: bool,
     *     count: int
     * }
     */
    public static function forBranchProduct(Branch $branch, Product $product): array
    {
        if (! self::usesBranchPricing()) {
            return self::forCatalogProduct($product);
        }

        $prices = BranchProductPrice::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('status', true)
            ->get();

        if ($prices->isEmpty()) {
            return [
                'amount' => null,
                'selling' => null,
                'effective' => null,
                'compare_at' => null,
                'has_discount' => false,
                'label' => null,
                'is_from' => false,
                'count' => 0,
            ];
        }

        /** @var BranchProductPrice $best */
        $best = $prices
            ->sortBy(fn (BranchProductPrice $price): float => self::effectiveAmount($price))
            ->first();

        $storefront = self::storefrontDisplay($best);
        $isFrom = $prices->count() > 1;
        $amount = $storefront['amount'];

        return [
            'amount' => $amount,
            'selling' => (float) $best->selling_price,
            'effective' => $amount,
            'compare_at' => $storefront['compare_at'],
            'has_discount' => $storefront['has_discount'],
            'label' => $amount === null
                ? null
                : ($isFrom
                    ? 'Start from $'.number_format($amount, 2)
                    : '$'.number_format($amount, 2)),
            'is_from' => $isFrom,
            'count' => $prices->count(),
        ];
    }

    /**
     * @return array{
     *     amount: float|null,
     *     selling: float|null,
     *     effective: float|null,
     *     compare_at: float|null,
     *     has_discount: bool,
     *     label: string|null,
     *     is_from: bool,
     *     count: int
     * }
     */
    public static function forCatalogProduct(Product $product): array
    {
        $list = self::listDisplayFromProduct($product);

        if ($list['selling_price'] === null) {
            return [
                'amount' => null,
                'selling' => null,
                'effective' => null,
                'compare_at' => null,
                'has_discount' => false,
                'label' => null,
                'is_from' => false,
                'count' => 0,
            ];
        }

        $selling = (float) $list['selling_price'];
        $compareAt = $list['has_compare_at'] && $list['regular_price'] !== null
            ? (float) $list['regular_price']
            : null;

        return [
            'amount' => $selling,
            'selling' => $selling,
            'effective' => $selling,
            'compare_at' => $compareAt,
            'has_discount' => false,
            'label' => '$'.number_format($selling, 2),
            'is_from' => false,
            'count' => 1,
        ];
    }

    /**
     * @return array{
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     regular_price: string|null,
     *     has_discount: bool,
     *     has_compare_at: bool,
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
                'regular_price' => null,
                'has_discount' => false,
                'has_compare_at' => false,
                'discount_type' => DiscountType::None->value,
                'discount_price' => null,
            ];
        }

        $selling = (float) $price->selling_price;
        $effective = self::effectiveAmount($price);
        $regular = $price->regular_price === null ? null : (float) $price->regular_price;
        $hasDiscount = self::discountIsLive($price) && $effective < $selling;
        $hasCompareAt = ! $hasDiscount && $regular !== null && $regular > $selling;

        return [
            'selling_price' => number_format($selling, 2, '.', ''),
            'effective_price' => number_format($effective, 2, '.', ''),
            'regular_price' => $regular === null ? null : number_format($regular, 2, '.', ''),
            'has_discount' => $hasDiscount,
            'has_compare_at' => $hasCompareAt,
            'discount_type' => $price->discount_type->value,
            'discount_price' => $price->discount_price === null
                ? null
                : (string) $price->discount_price,
        ];
    }

    /**
     * @return array{
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     regular_price: string|null,
     *     has_discount: bool,
     *     has_compare_at: bool,
     *     discount_type: string,
     *     discount_price: string|null
     * }
     */
    public static function listDisplayFromProduct(Product $product): array
    {
        if ($product->selling_price === null) {
            return [
                'selling_price' => null,
                'effective_price' => null,
                'regular_price' => null,
                'has_discount' => false,
                'has_compare_at' => false,
                'discount_type' => DiscountType::None->value,
                'discount_price' => null,
            ];
        }

        $selling = (float) $product->selling_price;
        $regular = $product->regular_price === null ? null : (float) $product->regular_price;
        $hasCompareAt = $regular !== null && $regular > $selling;

        return [
            'selling_price' => number_format($selling, 2, '.', ''),
            'effective_price' => number_format($selling, 2, '.', ''),
            'regular_price' => $regular === null ? null : number_format($regular, 2, '.', ''),
            'has_discount' => false,
            'has_compare_at' => $hasCompareAt,
            'discount_type' => DiscountType::None->value,
            'discount_price' => null,
        ];
    }

    /**
     * @return array{
     *     selling_price: string|null,
     *     effective_price: string|null,
     *     regular_price: string|null,
     *     has_discount: bool,
     *     has_compare_at: bool,
     *     discount_type: string,
     *     discount_price: string|null
     * }
     */
    public static function listDisplayForProductContext(?BranchProductPrice $price, Product $product): array
    {
        if (! self::usesBranchPricing()) {
            return self::listDisplayFromProduct($product);
        }

        return self::listDisplay($price);
    }

    /**
     * @return array{amount: float, compare_at: float|null, has_discount: bool, compare_at_formatted: string}
     */
    public static function storefrontDisplay(BranchProductPrice $price): array
    {
        $selling = (float) $price->selling_price;
        $effective = self::effectiveAmount($price);
        $regular = $price->regular_price === null ? null : (float) $price->regular_price;
        $hasDiscount = self::discountIsLive($price) && $effective < $selling;

        if ($hasDiscount) {
            return [
                'amount' => $effective,
                'compare_at' => $selling,
                'has_discount' => true,
                'compare_at_formatted' => '$'.number_format($selling, 2),
            ];
        }

        if ($regular !== null && $regular > $selling) {
            return [
                'amount' => $selling,
                'compare_at' => $regular,
                'has_discount' => false,
                'compare_at_formatted' => '$'.number_format($regular, 2),
            ];
        }

        return [
            'amount' => $selling,
            'compare_at' => null,
            'has_discount' => false,
            'compare_at_formatted' => '',
        ];
    }

    public static function discountIsLive(BranchProductPrice $price): bool
    {
        if ($price->discount_type === DiscountType::None) {
            return false;
        }

        $today = now()->toDateString();

        if ($price->start_date !== null && $today < $price->start_date->toDateString()) {
            return false;
        }

        if ($price->end_date !== null && $today > $price->end_date->toDateString()) {
            return false;
        }

        return true;
    }

    public static function effectiveAmount(BranchProductPrice $price): float
    {
        $selling = (float) $price->selling_price;

        if (! self::discountIsLive($price)) {
            return $selling;
        }

        return match ($price->discount_type) {
            DiscountType::Fixed => max(
                0,
                round($selling - (float) $price->discount_price, 2),
            ),
            DiscountType::Percent => max(
                0,
                round($selling * (1 - ((float) $price->discount_price / 100)), 2),
            ),
            default => $selling,
        };
    }

    public static function usesBranchPricing(): bool
    {
        return (bool) config('catalog.show_branch_pricing');
    }

    public static function productHasCatalogSellingPrice(Product $product): bool
    {
        return $product->selling_price !== null;
    }
}
