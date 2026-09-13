<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class VariantService
{
    /**
     * @param  array{
     *     name: string,
     *     sku?: string|null,
     *     attributes?: array<string, mixed>|null,
     *     status?: bool
     * }  $data
     */
    public function create(Product $product, array $data): ProductVariant
    {
        return DB::transaction(function () use ($product, $data): ProductVariant {
            return ProductVariant::query()->create([
                'product_id' => $product->id,
                'name' => $data['name'],
                'sku' => $data['sku'] ?? null,
                'attributes' => $data['attributes'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
        });
    }

    /**
     * @param  array{
     *     name?: string,
     *     sku?: string|null,
     *     attributes?: array<string, mixed>|null,
     *     status?: bool
     * }  $data
     */
    public function update(ProductVariant $variant, array $data): ProductVariant
    {
        $variant->fill($data);
        $variant->save();

        return $variant->refresh();
    }

    public function delete(ProductVariant $variant): void
    {
        $variant->delete();
    }

    public function restore(ProductVariant $variant): ProductVariant
    {
        $variant->restore();

        return $variant->refresh();
    }
}
