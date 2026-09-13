<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductImage;
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
     * @param  list<UploadedFile>  $galleryImages
     */
    public function createFromPlatform(
        array $data,
        ?UploadedFile $primaryImage = null,
        array $galleryImages = [],
        ?User $actor = null,
    ): Product {
        return DB::transaction(function () use ($data, $primaryImage, $galleryImages, $actor): Product {
            return $this->createProduct($data, $primaryImage, $galleryImages, $actor, enableForBranch: null);
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
     * @param  list<UploadedFile>  $galleryImages
     */
    public function createFromBranch(
        Branch $branch,
        array $data,
        ?UploadedFile $primaryImage = null,
        array $galleryImages = [],
        ?User $actor = null,
    ): Product {
        return DB::transaction(function () use ($branch, $data, $primaryImage, $galleryImages, $actor): Product {
            return $this->createProduct($data, $primaryImage, $galleryImages, $actor, enableForBranch: $branch);
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
     *     remove_image?: bool,
     *     remove_gallery_ids?: list<int>
     * }  $data
     * @param  list<UploadedFile>  $galleryImages
     */
    public function update(
        Product $product,
        array $data,
        ?UploadedFile $primaryImage = null,
        array $galleryImages = [],
    ): Product {
        return DB::transaction(function () use ($product, $data, $primaryImage, $galleryImages): Product {
            if (($data['remove_image'] ?? false) === true && $product->image) {
                Storage::disk('public')->delete($product->image);
                $product->image = null;
            }

            if ($primaryImage !== null) {
                if ($product->image) {
                    Storage::disk('public')->delete($product->image);
                }
                $product->image = $primaryImage->store('products', 'public');
            }

            /** @var list<int> $removeGalleryIds */
            $removeGalleryIds = $data['remove_gallery_ids'] ?? [];
            unset($data['remove_image'], $data['remove_gallery_ids']);

            if ($removeGalleryIds !== []) {
                $this->deleteGalleryImages($product, $removeGalleryIds);
            }

            $product->fill($data);
            $product->save();

            if ($galleryImages !== []) {
                $this->appendGalleryImages($product, $galleryImages);
            }

            return $product->refresh()->load('images');
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
     * @param  list<UploadedFile>  $galleryImages
     */
    private function createProduct(
        array $data,
        ?UploadedFile $primaryImage,
        array $galleryImages,
        ?User $actor,
        ?Branch $enableForBranch,
    ): Product {
        $primaryPath = $primaryImage?->store('products', 'public');

        if ($primaryPath === null && $galleryImages !== []) {
            $primaryPath = $galleryImages[0]->store('products', 'public');
            $galleryImages = array_slice($galleryImages, 1);
        }

        $product = Product::query()->create([
            'category_id' => $data['category_id'],
            'name' => $data['name'],
            'slug' => $data['slug'],
            'sku' => $data['sku'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $primaryPath,
            'status' => $data['status'] ?? true,
            'created_by' => $actor?->id,
        ]);

        if ($galleryImages !== []) {
            $this->appendGalleryImages($product, $galleryImages);
        }

        if ($enableForBranch !== null) {
            $this->availability->enableForBranchOnly($enableForBranch, $product);
        }

        return $product->refresh()->load('images');
    }

    /**
     * @param  list<UploadedFile>  $galleryImages
     */
    private function appendGalleryImages(Product $product, array $galleryImages): void
    {
        $nextSort = (int) $product->images()->max('sort_order');

        foreach ($galleryImages as $file) {
            $nextSort++;

            ProductImage::query()->create([
                'product_id' => $product->id,
                'path' => $file->store('products/gallery', 'public'),
                'sort_order' => $nextSort,
            ]);
        }
    }

    /**
     * @param  list<int>  $imageIds
     */
    private function deleteGalleryImages(Product $product, array $imageIds): void
    {
        $images = ProductImage::query()
            ->where('product_id', $product->id)
            ->whereIn('id', $imageIds)
            ->get();

        foreach ($images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }
    }
}
