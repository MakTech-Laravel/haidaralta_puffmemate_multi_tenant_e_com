<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\CatalogBackgroundColor;
use App\Support\ProductSpecifications;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductService
{
    /**
     * @param  array{
     *     category_ids: list<int>,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     color_code?: array<string, mixed>|null
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
            return $this->createProduct($data, $primaryImage, $galleryImages, $actor);
        });
    }

    /**
     * @param  array{
     *     category_ids: list<int>,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     color_code?: array<string, mixed>|null
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
        return DB::transaction(function () use ($data, $primaryImage, $galleryImages, $actor): Product {
            return $this->createProduct($data, $primaryImage, $galleryImages, $actor);
        });
    }

    /**
     * @param  array{
     *     category_ids?: list<int>,
     *     name?: string,
     *     slug?: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     color_code?: array<string, mixed>|null,
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
            /** @var list<int>|null $categoryIds */
            $categoryIds = $data['category_ids'] ?? null;
            unset($data['remove_image'], $data['remove_gallery_ids'], $data['category_ids']);

            if (array_key_exists('color_code', $data)) {
                $data['color_code'] = CatalogBackgroundColor::normalize(
                    is_array($data['color_code']) ? $data['color_code'] : null,
                );
            }

            if (array_key_exists('specifications', $data)) {
                $data['specifications'] = ProductSpecifications::normalize($data['specifications']);
            }

            if (array_key_exists('tags', $data) && is_array($data['tags'])) {
                $data['tags'] = array_values(array_unique(array_map('strval', $data['tags'])));
            }

            if (array_key_exists('pieces_per_pack', $data)) {
                $data['pieces_per_pack'] = $data['pieces_per_pack'] === null || $data['pieces_per_pack'] === ''
                    ? null
                    : (int) $data['pieces_per_pack'];
            }

            if (! (bool) config('catalog.show_branch_pricing')) {
                if (array_key_exists('selling_price', $data)) {
                    $data['selling_price'] = $this->nullableCatalogMoney($data['selling_price']);
                }

                if (array_key_exists('regular_price', $data)) {
                    $data['regular_price'] = $this->nullableCatalogMoney($data['regular_price']);
                }
            } else {
                unset($data['selling_price'], $data['regular_price']);
            }

            if ($removeGalleryIds !== []) {
                $this->deleteGalleryImages($product, $removeGalleryIds);
            }

            $product->fill($data);
            $product->save();

            if ($categoryIds !== null) {
                $product->categories()->sync($categoryIds);
            }

            if ($galleryImages !== []) {
                $this->appendGalleryImages($product, $galleryImages);
            }

            return $product->refresh()->load(['images', 'categories:id,name,slug']);
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
     *     category_ids: list<int>,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     color_code?: array<string, mixed>|null
     * }  $data
     * @param  list<UploadedFile>  $galleryImages
     */
    private function createProduct(
        array $data,
        ?UploadedFile $primaryImage,
        array $galleryImages,
        ?User $actor,
    ): Product {
        $primaryPath = $primaryImage?->store('products', 'public');

        if ($primaryPath === null && $galleryImages !== []) {
            $primaryPath = $galleryImages[0]->store('products', 'public');
            $galleryImages = array_slice($galleryImages, 1);
        }

        $categoryIds = array_values(array_unique(array_map('intval', $data['category_ids'])));

        $product = Product::query()->create([
            'name' => $data['name'],
            'slug' => $data['slug'],
            'sku' => filled($data['sku'] ?? null)
                ? $data['sku']
                : Product::skuFromSlug((string) $data['slug']),
            'description' => $data['description'] ?? null,
            'brand' => $data['brand'] ?? null,
            'pack_label' => $data['pack_label'] ?? null,
            'pieces_per_pack' => array_key_exists('pieces_per_pack', $data) && $data['pieces_per_pack'] !== null && $data['pieces_per_pack'] !== ''
                ? (int) $data['pieces_per_pack']
                : null,
            'pack_unit' => $data['pack_unit'] ?? null,
            'tags' => array_values(array_unique(array_map(
                'strval',
                is_array($data['tags'] ?? null) ? $data['tags'] : [],
            ))),
            'specifications' => ProductSpecifications::normalize($data['specifications'] ?? []),
            'image' => $primaryPath,
            'color_code' => CatalogBackgroundColor::normalize($data['color_code'] ?? null),
            'selling_price' => $this->nullableCatalogMoney($data['selling_price'] ?? null),
            'regular_price' => $this->nullableCatalogMoney($data['regular_price'] ?? null),
            'status' => $data['status'] ?? true,
            'created_by' => $actor?->id,
        ]);

        $product->categories()->sync($categoryIds);

        if ($galleryImages !== []) {
            $this->appendGalleryImages($product, $galleryImages);
        }

        return $product->refresh()->load(['images', 'categories:id,name,slug']);
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

    private function nullableCatalogMoney(mixed $value): ?string
    {
        if (! (bool) config('catalog.show_branch_pricing')) {
            if ($value === null || $value === '') {
                return null;
            }

            return number_format((float) $value, 2, '.', '');
        }

        return null;
    }
}
