<?php

namespace App\Services\Inventory;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\Product;
use Illuminate\Support\Facades\DB;

class BranchProductAvailabilityService
{
    public function isEnabled(Branch $branch, Product $product): bool
    {
        return BranchProduct::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('status', true)
            ->exists();
    }

    public function setEnabled(Branch $branch, Product $product, bool $enabled): BranchProduct
    {
        return DB::transaction(function () use ($branch, $product, $enabled): BranchProduct {
            $row = BranchProduct::query()->firstOrNew([
                'branch_id' => $branch->id,
                'product_id' => $product->id,
            ]);

            $row->status = $enabled;
            $row->save();

            return $row->refresh();
        });
    }

    public function enableForBranchOnly(Branch $branch, Product $product): BranchProduct
    {
        return $this->setEnabled($branch, $product, true);
    }
}
