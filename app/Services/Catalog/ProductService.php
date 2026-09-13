<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\Inventory\BranchProductAvailabilityService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    public function __construct(
        public BranchProductAvailabilityService $availability,
    ) {}

    /**
     * @param  array{
     *     category_id: int,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool
     * }  $data
     */
    public function createFromPlatform(array $data, ?UploadedFile $image = null, ?User $actor = null): Product
    {
        return DB::transaction(function () use ($data, $image, $actor): Product {
            return $this->createProduct($data, $image, $actor, enableForBranch: null);
        });
    }

    /**
     * @param  array{
     *     category_id: int,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool
     * }  $data
     */
    public function createFromBranch(
        Branch $branch,
        array $data,
        ?UploadedFile $image = null,
        ?User $actor = null,
    ): Product {
        return DB::transaction(function () use ($branch, $data, $image, $actor): Product {
            return $this->createProduct($data, $image, $actor, enableForBranch: $branch);
        });
    }

    /**
     * @param  array{
     *     category_id?: int,
     *     name?: string,
     *     slug?: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     remove_image?: bool
     * }  $data
     */
    public function update(Product $product, array $data, ?UploadedFile $image = null): Product
    {
        return DB::transaction(function () use ($product, $data, $image): Product {
            if (($data['remove_image'] ?? false) === true && $product->image) {
                Storage::disk('public')->delete($product->image);
                $product->image = null;
            }

            if ($image !== null) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->image = $image->store('products', 'public');
            }

            unset($data['remove_image']);
            $product->fill($data);
            $product->save();

            return $product->refresh();
        });
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }

    public function restore(Product $product): Product
    {
        $product->restore();

        return $product->refresh();
    }

    /**
     * @param  array{
     *     category_id: int,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool
     * }  $data
     */
    private function createProduct(
        array $data,
        ?UploadedFile $image,
        ?User $actor,
        ?Branch $enableForBranch,
    ): Product {
        $product = Product::query()->create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $image?->store('products', 'public'),
            'status' => $data['status'] ?? true,
            'created_by' => $actor?->id,
        ]);

        if ($enableForBranch !== null) {
            $this->availability->enableForBranchOnly($enableForBranch, $product);
        }

        return $product->refresh();
    }
}
