<?php

namespace App\Services\Catalog;

use App\Models\Branch;
use App\Models\Product;
use App\Models\User;
use App\Services\Media\MediaUploadService;
use App\Support\CatalogBackgroundColor;
use App\Support\ProductSpecifications;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class ProductService
{
    public function __construct(
        private MediaUploadService $media,
        private ProductPackageService $packages,
    ) {}

    /**
     * @param  array{
     *     category_ids: list<int>,
     *     name: string,
     *     slug: string,
     *     sku?: string|null,
     *     description?: string|null,
     *     status?: bool,
     *     packages_enabled?: bool,
     *     packages?: list<array{
     *         label: string,
     *         quantity: int|string,
     *         price: float|string|null,
     *         status?: bool,
     *         is_default?: bool,
     *         sort_order?: int|string
     *     }>,
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
            /** @var list<array{label: string, quantity: int|string, price: float|string|null, status?: bool, is_default?: bool, sort_order?: int|string}> $packages */
            $packages = [];

            if (array_key_exists('packages', $data) && is_array($data['packages'])) {
                $packages = array_values($data['packages']);
            }

            unset($data['packages']);

            $product = $this->createProduct($data, $primaryImage, $galleryImages, $actor);

            if ((bool) config('catalog.show_product_packages')) {
                $defaultDraft = null;

                foreach ($packages as $index => $package) {
                    if ((bool) ($package['is_default'] ?? false)) {
                        $defaultDraft = $package;
                        unset($packages[$index]);

                        break;
                    }
                }

                $this->packages->createDefault(
                    $product,
                    $defaultDraft['price'] ?? null,
                    $defaultDraft === null || ! array_key_exists('status', $defaultDraft)
                        ? true
                        : (bool) $defaultDraft['status'],
                    (int) ($defaultDraft['sort_order'] ?? 0),
                    isset($defaultDraft['label']) ? (string) $defaultDraft['label'] : null,
                );

                foreach ($packages as $package) {
                    $this->packages->create($product, [
                        'label' => $package['label'],
                        'quantity' => (int) $package['quantity'],
                        'price' => $package['price'],
                        'status' => array_key_exists('status', $package)
                            ? (bool) $package['status']
                            : true,
                        'sort_order' => (int) ($package['sort_order'] ?? 0),
                    ]);
                }
            }

            return $product->refresh()->load(['media', 'categories:id,name,slug', 'packages']);
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
            $removeImage = (bool) ($data['remove_image'] ?? false);

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

            if (array_key_exists('wholesale_price', $data)) {
                $data['wholesale_price'] = $this->nullableMoney($data['wholesale_price']);
            }

            if (array_key_exists('min_wholesale_qty', $data)) {
                $data['min_wholesale_qty'] = $this->nullablePositiveInt($data['min_wholesale_qty']);
            }

            $product->fill($data);
            $product->save();

            $this->media->syncSingle($product, 'image', $primaryImage, $removeImage && $primaryImage === null);

            if ($removeGalleryIds !== []) {
                $this->media->deleteFromCollection($product, 'gallery', $removeGalleryIds);
            }

            if ($categoryIds !== null) {
                $product->categories()->sync($categoryIds);
            }

            if ($galleryImages !== []) {
                $this->media->syncMany($product, 'gallery', $galleryImages);
            }

            return $product->refresh()->load(['media', 'categories:id,name,slug']);
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
            'color_code' => CatalogBackgroundColor::normalize($data['color_code'] ?? null),
            'selling_price' => $this->nullableCatalogMoney($data['selling_price'] ?? null),
            'regular_price' => $this->nullableCatalogMoney($data['regular_price'] ?? null),
            'wholesale_price' => $this->nullableMoney($data['wholesale_price'] ?? null),
            'min_wholesale_qty' => $this->nullablePositiveInt($data['min_wholesale_qty'] ?? null),
            'status' => $data['status'] ?? true,
            'packages_enabled' => array_key_exists('packages_enabled', $data)
                ? (bool) $data['packages_enabled']
                : true,
            'created_by' => $actor?->id,
        ]);

        $product->categories()->sync($categoryIds);

        if ($primaryImage === null && $galleryImages !== []) {
            $primaryImage = $galleryImages[0];
            $galleryImages = array_slice($galleryImages, 1);
        }

        $this->media->syncSingle($product, 'image', $primaryImage);

        if ($galleryImages !== []) {
            $this->media->syncMany($product, 'gallery', $galleryImages);
        }

        return $product->refresh()->load(['media', 'categories:id,name,slug']);
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

    private function nullableMoney(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private function nullablePositiveInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        $int = (int) $value;

        return $int > 0 ? $int : null;
    }
}
