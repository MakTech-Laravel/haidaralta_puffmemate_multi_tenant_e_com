<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Support\Facades\DB;

class BranchPricingService
{
    /**
     * @param  array{
     *     selling_price: float|string,
     *     discount_price?: float|string|null,
     *     start_date?: string|null,
     *     end_date?: string|null,
     *     status?: bool,
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

            $price->fill([
                'selling_price' => $data['selling_price'],
                'discount_price' => $data['discount_price'] ?? null,
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,
                'status' => $data['status'] ?? true,
            ]);
            $price->save();

            return $price->refresh();
        });
    }
}
