<?php

namespace App\Services\Catalog;

use App\Models\Product;
use App\Models\ProductPackage;
use Illuminate\Support\Facades\DB;

class ProductPackageService
{
    /**
     * @param  array{
     *     label: string,
     *     quantity: int,
     *     price: float|string,
     *     status?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function create(Product $product, array $data): ProductPackage
    {
        return DB::transaction(function () use ($product, $data): ProductPackage {
            return ProductPackage::query()->create([
                'product_id' => $product->id,
                'label' => $data['label'],
                'quantity' => (int) $data['quantity'],
                'price' => number_format((float) $data['price'], 2, '.', ''),
                'status' => $data['status'] ?? true,
                'sort_order' => (int) ($data['sort_order'] ?? 0),
            ]);
        });
    }

    /**
     * @param  array{
     *     label?: string,
     *     quantity?: int,
     *     price?: float|string,
     *     status?: bool,
     *     sort_order?: int
     * }  $data
     */
    public function update(ProductPackage $package, array $data): ProductPackage
    {
        if (array_key_exists('price', $data)) {
            $data['price'] = number_format((float) $data['price'], 2, '.', '');
        }

        if (array_key_exists('quantity', $data)) {
            $data['quantity'] = (int) $data['quantity'];
        }

        if (array_key_exists('sort_order', $data)) {
            $data['sort_order'] = (int) $data['sort_order'];
        }

        $package->fill($data);
        $package->save();

        return $package->refresh();
    }

    public function delete(ProductPackage $package): void
    {
        $package->delete();
    }

    public function restore(ProductPackage $package): ProductPackage
    {
        $package->restore();

        return $package->refresh();
    }
}
