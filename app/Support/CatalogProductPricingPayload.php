<?php

namespace App\Support;

use App\Models\Product;

final class CatalogProductPricingPayload
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
     *     status: bool,
     *     price: array{
     *         regular_price: mixed,
     *         selling_price: mixed,
     *         discount_type: string,
     *         discount_price: mixed,
     *         start_date: ?string,
     *         end_date: ?string,
     *         effective_price: ?string,
     *         has_discount: bool,
     *         has_compare_at: bool
     *     }
     * }
     */
    public static function product(Product $product): array
    {
        $product->loadMissing(['categories:id,name,slug']);
        $display = BranchProductPriceDisplay::listDisplayFromProduct($product);

        return [
            'id' => $product->id,
            'name' => $product->name,
            'slug' => $product->slug,
            'sku' => $product->sku,
            'image_url' => $product->imageUrl(),
            'color_code' => $product->color_code,
            'category_name' => $product->categoryNamesLabel(),
            'status' => (bool) $product->status,
            'price' => [
                'regular_price' => $product->regular_price,
                'selling_price' => $product->selling_price,
                'discount_type' => $product->discount_type->value,
                'discount_price' => $product->discount_price,
                'start_date' => $product->discount_start_date?->toDateString(),
                'end_date' => $product->discount_end_date?->toDateString(),
                'effective_price' => $display['effective_price'],
                'has_discount' => $display['has_discount'],
                'has_compare_at' => $display['has_compare_at'],
            ],
        ];
    }
}
