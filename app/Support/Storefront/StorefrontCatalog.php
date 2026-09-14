<?php

namespace App\Support\Storefront;

use App\Enums\ProductTag;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Product;
use App\Support\BranchProductPriceDisplay;
use App\Support\MediaUrl;
use App\Support\ProductSpecifications;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontCatalog
{
    public const BRANCH_SESSION_KEY = 'storefront.branch_slug';

    /**
     * @return list<array{label: string, categorySlug: string}>
     */
    public static function navCategories(): array
    {
        $limit = (int) config('catalog.nav_categories_max', 6);

        return Category::query()
            ->where('status', true)
            ->where('show_in_nav', true)
            ->orderByRaw('CASE WHEN nav_sort IS NULL THEN 1 ELSE 0 END')
            ->orderBy('nav_sort')
            ->orderBy('name')
            ->limit($limit)
            ->get(['name', 'slug'])
            ->map(fn (Category $category): array => [
                'label' => $category->name,
                'categorySlug' => $category->slug,
            ])
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, description: string, image: string, href: string, wide: bool}>
     */
    public static function featuredCategories(): array
    {
        $limit = (int) config('catalog.featured_categories_max', 5);

        $categories = Category::query()
            ->where('status', true)
            ->where('featured', true)
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return $categories
            ->values()
            ->map(function (Category $category, int $index) use ($categories): array {
                $total = $categories->count();

                return [
                    'id' => $category->slug,
                    'name' => $category->name,
                    'description' => $category->slogan ?: 'Shop '.$category->name,
                    'image' => MediaUrl::resolve($category->image) ?? '/images/storefront/logo.png',
                    'href' => route('shop.category', ['category' => $category->slug], false),
                    'wide' => $total > 3 && $index >= $total - 2,
                ];
            })
            ->all();
    }

    /**
     * @return list<array{id: string, name: string}>
     */
    public static function branches(): array
    {
        return Branch::query()
            ->where('status', true)
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Branch $branch): array => [
                'id' => $branch->slug,
                'name' => $branch->name,
            ])
            ->values()
            ->all();
    }

    public static function resolveBranch(?string $slug): ?Branch
    {
        if ($slug === null || $slug === '') {
            return null;
        }

        return Branch::query()
            ->where('status', true)
            ->where('slug', $slug)
            ->first();
    }

    public static function selectedBranch(Request $request): ?Branch
    {
        $fromQuery = $request->string('branch')->trim()->toString();

        if ($fromQuery !== '') {
            $branch = self::resolveBranch($fromQuery);

            if ($branch !== null) {
                $request->session()->put(self::BRANCH_SESSION_KEY, $branch->slug);

                return $branch;
            }
        }

        return self::resolveBranch($request->session()->get(self::BRANCH_SESSION_KEY));
    }

    /**
     * @return array{slug: string, name: string, slogan: string|null}
     */
    public static function resolveCategory(string $slug): array
    {
        $category = Category::query()
            ->where('status', true)
            ->where('slug', $slug)
            ->first();

        if ($category === null) {
            throw new NotFoundHttpException;
        }

        return [
            'slug' => $category->slug,
            'name' => $category->name,
            'slogan' => $category->slogan,
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
     */
    public static function productsForCategory(string $slug, ?Branch $branch = null, ?string $search = null): array
    {
        $category = Category::query()
            ->where('status', true)
            ->where('slug', $slug)
            ->first();

        if ($category === null) {
            throw new NotFoundHttpException;
        }

        $query = Product::query()
            ->where('status', true)
            ->whereHas('categories', fn ($builder) => $builder->where('categories.id', $category->id))
            ->with(['categories:id,name,slug', 'images'])
            ->orderBy('name');

        if (is_string($search) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        if ($branch !== null) {
            $query->whereHas('branchProducts', function ($builder) use ($branch): void {
                $builder->where('branch_id', $branch->id)->where('status', true);
            });
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
     */
    public static function products(?Branch $branch = null, int $limit = 24): array
    {
        $query = Product::query()
            ->where('status', true)
            ->with(['categories:id,name,slug', 'images'])
            ->orderBy('name')
            ->limit($limit);

        if ($branch !== null) {
            $query->whereHas('branchProducts', function ($builder) use ($branch): void {
                $builder->where('branch_id', $branch->id)->where('status', true);
            });
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
     */
    public static function productsWithTag(ProductTag $tag, ?Branch $branch = null, int $limit = 8): array
    {
        $query = Product::query()
            ->where('status', true)
            ->whereJsonContains('tags', $tag->value)
            ->with(['categories:id,name,slug', 'images'])
            ->orderBy('name')
            ->limit($limit);

        if ($branch !== null) {
            $query->whereHas('branchProducts', function ($builder) use ($branch): void {
                $builder->where('branch_id', $branch->id)->where('status', true);
            });
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function productDetail(string $slug, ?Branch $branch = null): array
    {
        $product = Product::query()
            ->where('status', true)
            ->where('slug', $slug)
            ->with(['categories:id,name,slug', 'images', 'variants'])
            ->first();

        if ($product === null) {
            throw new NotFoundHttpException;
        }

        $primaryCategory = $product->categories->first();
        $categoryName = $primaryCategory?->name ?? 'Catalog';
        $categorySlug = $primaryCategory?->slug;
        $image = MediaUrl::resolve($product->image) ?? '/images/storefront/logo.png';
        $gallery = collect([
            $image,
            ...$product->images->map(fn ($img): string => MediaUrl::resolve($img->path) ?? $image)->all(),
        ])->filter()->unique()->values()->all();

        $price = $branch !== null
            ? BranchProductPriceDisplay::forBranchProduct($branch, $product)
            : ['amount' => null, 'label' => null, 'is_from' => false, 'count' => 0];

        $stock = $branch !== null ? self::stockFor($branch, $product) : 0;
        $tagBadges = collect($product->tags ?? [])
            ->map(fn (string $tag): ?string => ProductTag::tryFrom($tag)?->label())
            ->filter()
            ->values()
            ->all();

        $compareAt = null;
        $displayPrice = $price['label'] ?? 'Select a branch for pricing';

        return [
            'id' => $product->slug,
            'name' => $product->name,
            'description' => self::cardDescription($product),
            'category' => $categoryName,
            'categorySlug' => $categorySlug,
            'badges' => array_values(array_unique(array_filter([$categoryName, ...$tagBadges]))),
            'images' => $gallery !== [] ? $gallery : [$image],
            'price' => $displayPrice,
            'compareAtPrice' => $compareAt ?? '',
            'availability' => $branch === null
                ? 'Select a branch'
                : ($stock > 0 ? 'Available' : 'Out of stock'),
            'rating' => 0,
            'reviewCount' => 0,
            'about' => $product->description ?: 'Branch stock and pricing may vary — select your nearest branch for accurate availability.',
            'branch' => $branch?->name ?? 'No branch selected',
            'stock' => $stock,
            'cardPrice' => $displayPrice,
            'image' => $image,
            'brand' => $product->brand,
            'variants' => $product->variants
                ->where('status', true)
                ->map(fn ($variant): array => [
                    'id' => $variant->id,
                    'name' => $variant->name,
                    'sku' => $variant->sku,
                    'attributes' => $variant->attributes ?? [],
                ])
                ->values()
                ->all(),
            'specifications' => ProductSpecifications::forDisplay(
                $categoryName,
                $product->brand,
                $product->specifications,
            ),
            'shippingPoints' => [
                'Same-day delivery available in selected local zones when ordered before cut-off.',
                'Stock is confirmed against your selected branch before dispatch.',
                'Cash on delivery and EFTPOS on delivery are supported where offered.',
                'Photo ID may be required on delivery for age-restricted products.',
                'Delivery windows depend on branch workload and zone coverage.',
                'If an item is out of stock at your branch, we will notify you before shipping.',
                'Contact support for wholesale or bulk delivery arrangements.',
            ],
            'responsiblePoints' => [
                'Tobacco and nicotine products are for adults 18+ only.',
                'We do not sell to minors and may request government-issued ID.',
                'Products carry mandatory health warnings as required by law.',
                'Advertising and promotions comply with Australian retail rules.',
                'Please store products securely and away from children.',
                'If you are pregnant or have health concerns, seek medical advice before use.',
                'Dispose of packaging and waste responsibly.',
            ],
            'reviews' => [],
        ];
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}>
     */
    public static function similarProducts(Product $product, ?Branch $branch = null, int $limit = 4): array
    {
        $categoryIds = $product->categories->pluck('id');

        $query = Product::query()
            ->where('status', true)
            ->where('id', '!=', $product->id)
            ->with(['categories:id,name,slug', 'images'])
            ->orderBy('name')
            ->limit($limit);

        if ($categoryIds->isNotEmpty()) {
            $query->whereHas('categories', fn ($builder) => $builder->whereIn('categories.id', $categoryIds));
        }

        if ($branch !== null) {
            $query->whereHas('branchProducts', function ($builder) use ($branch): void {
                $builder->where('branch_id', $branch->id)->where('status', true);
            });
        }

        return $query->get()
            ->map(fn (Product $item): array => self::mapProductCard($item, $branch))
            ->values()
            ->all();
    }

    /**
     * @return array{id: string, name: string, description: string, price: string, stock: int, category: string, image: string}
     */
    public static function mapProductCard(Product $product, ?Branch $branch = null): array
    {
        if (! $product->relationLoaded('categories')) {
            $product->load('categories:id,name,slug');
        }

        $categoryName = $product->categories->first()?->name ?? 'Catalog';
        $price = $branch !== null
            ? BranchProductPriceDisplay::forBranchProduct($branch, $product)
            : ['label' => null];

        return [
            'id' => $product->slug,
            'name' => $product->name,
            'description' => self::cardDescription($product),
            'price' => $price['label'] ?? 'Select a branch',
            'stock' => $branch !== null ? self::stockFor($branch, $product) : 0,
            'category' => $categoryName,
            'image' => MediaUrl::resolve($product->image) ?? '/images/storefront/logo.png',
        ];
    }

    private static function cardDescription(Product $product): string
    {
        $specs = ProductSpecifications::normalize($product->specifications);
        $bits = Collection::make($specs)
            ->take(2)
            ->map(fn (array $row): string => $row['value'] !== '' ? $row['value'] : $row['label'])
            ->filter()
            ->values();

        if ($bits->isNotEmpty()) {
            return $bits->implode(' • ');
        }

        if (is_string($product->brand) && $product->brand !== '') {
            return $product->brand;
        }

        $text = trim(strip_tags((string) $product->description));

        if ($text === '') {
            return $product->name;
        }

        return str($text)->limit(80)->toString();
    }

    private static function stockFor(Branch $branch, Product $product): int
    {
        return (int) Inventory::query()
            ->where('branch_id', $branch->id)
            ->where('product_id', $product->id)
            ->where('status', true)
            ->sum('available_quantity');
    }
}
