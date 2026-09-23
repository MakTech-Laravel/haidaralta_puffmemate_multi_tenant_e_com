<?php

namespace App\Services\Catalog;

use App\Enums\DiscountType;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class CatalogPricingService
{
    /**
     * @param  array{
     *     selling_price: float|string,
     *     regular_price?: float|string|null,
     *     discount_type?: string|DiscountType|null,
     *     discount_price?: float|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null
     * }  $data
     */
    public function upsert(Product $product, array $data): Product
    {
        return DB::transaction(function () use ($product, $data): Product {
            $discountType = $this->resolveDiscountType(
                $data['discount_type'] ?? null,
                $data['discount_price'] ?? null,
            );
            $hasDiscount = $discountType !== DiscountType::None;

            $product->fill([
                'regular_price' => array_key_exists('regular_price', $data)
                    ? self::nullableMoney($data['regular_price'])
                    : $product->regular_price,
                'selling_price' => self::nullableMoney($data['selling_price']) ?? (string) $data['selling_price'],
                'discount_type' => $discountType,
                'discount_price' => $hasDiscount ? self::nullableMoney($data['discount_price'] ?? null) : null,
                'discount_start_date' => $hasDiscount ? ($data['start_date'] ?? null) : null,
                'discount_end_date' => $hasDiscount ? ($data['end_date'] ?? null) : null,
            ]);

            if (! $product->isDirty()) {
                return $product;
            }

            $product->save();

            return $product->refresh();
        });
    }

    private function resolveDiscountType(mixed $type, mixed $discountPrice): DiscountType
    {
        if ($type instanceof DiscountType) {
            return $type;
        }

        if (is_string($type) && $type !== '') {
            return DiscountType::tryFrom($type) ?? DiscountType::None;
        }

        if ($discountPrice !== null && $discountPrice !== '') {
            return DiscountType::Fixed;
        }

        return DiscountType::None;
    }

    private static function nullableMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }
}
