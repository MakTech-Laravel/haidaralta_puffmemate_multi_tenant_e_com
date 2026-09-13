<?php

namespace App\Services\Inventory;

use App\Enums\DiscountType;
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
     *     discount_type?: string|DiscountType|null,
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

            $discountType = $this->resolveDiscountType($data['discount_type'] ?? null, $data['discount_price'] ?? null);
            $hasDiscount = $discountType !== DiscountType::None;

            $price->fill([
                'selling_price' => $data['selling_price'],
                'discount_type' => $discountType,
                'discount_price' => $hasDiscount ? ($data['discount_price'] ?? null) : null,
                'start_date' => $hasDiscount ? ($data['start_date'] ?? null) : null,
                'end_date' => $hasDiscount ? ($data['end_date'] ?? null) : null,
                'status' => $data['status'] ?? true,
            ]);
            $price->save();

            return $price->refresh();
        });
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
