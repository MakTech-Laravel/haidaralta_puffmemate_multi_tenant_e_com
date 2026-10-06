<?php

namespace App\Support\Storefront;

use App\Enums\DiscountType;
use App\Enums\ProductTag;
use App\Models\Branch;
use App\Models\Category;
use App\Models\Inventory;
use App\Models\Pack;
use App\Models\PackItem;
use App\Models\Product;
use App\Models\ProductReview;
use App\Support\BranchProductPriceDisplay;
use App\Support\CompactNumber;
use App\Support\MediaUrl;
use App\Support\ProductSpecifications;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

class StorefrontCatalog
{
    public const BRANCH_SESSION_KEY = 'storefront.branch_slug';

    public const NAV_CACHE_KEY = 'storefront.nav_categories';

    public const NAV_VERSION_KEY = 'storefront.nav_categories.version';

    /**
     * @return list<array{label: string, categorySlug: string}>
     */
    public static function navCategories(): array
    {
        /** @var list<array{label: string, categorySlug: string}> */
        return Cache::rememberForever(self::NAV_CACHE_KEY, fn (): array => self::queryNavCategories());
    }

    /**
     * Cache version for Inertia once-prop keys so clients refetch after nav edits.
     */
    public static function navCategoriesVersion(): string
    {
        return (string) Cache::get(self::NAV_VERSION_KEY, '0');
    }

    public static function forgetNavCategories(): void
    {
        Cache::forget(self::NAV_CACHE_KEY);
        Cache::forever(self::NAV_VERSION_KEY, (string) now()->getTimestampMs());
    }

    /**
     * @return list<array{label: string, categorySlug: string}>
     */
    private static function queryNavCategories(): array
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
            ->orderByRaw("CASE slug
                WHEN 'cigarettes' THEN 1
                WHEN 'vapes' THEN 2
                WHEN 'tobacco' THEN 3
                WHEN 'nicotine-pouches' THEN 4
                WHEN 'accessories' THEN 5
                ELSE 99
            END")
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
                    'image' => $category->imageUrl(MediaUrl::DEFAULT_FALLBACK),
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
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public static function productsForCategory(string $slug, ?Branch $branch = null, ?string $search = null, ?string $sort = null): array
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
            ->with(['categories:id,name,slug', 'media']);

        match ($sort) {
            'name_desc' => $query->orderByDesc('name'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };

        if (is_string($search) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%');
            });
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public static function products(
        ?Branch $branch = null,
        ?string $search = null,
        ?string $sort = null,
        ?int $limit = null,
    ): array {
        $query = Product::query()
            ->where('status', true)
            ->with(['categories:id,name,slug', 'media']);

        match ($sort) {
            'name_desc' => $query->orderByDesc('name'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderBy('name'),
        };

        if (is_string($search) && $search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * Products with a compare-at / offer price for Packs & Saves.
     *
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public static function productsOnOffer(?Branch $branch = null, ?int $limit = null): array
    {
        $today = now()->toDateString();

        $query = Product::query()
            ->where('status', true)
            ->where(function ($builder) use ($today): void {
                $builder->where(function ($offer): void {
                    $offer->whereNotNull('selling_price')
                        ->whereNotNull('regular_price')
                        ->whereColumn('regular_price', '>', 'selling_price');
                })->orWhere(function ($discount) use ($today): void {
                    $discount->where('discount_type', '!=', DiscountType::None->value)
                        ->whereNotNull('discount_price')
                        ->whereNotNull('selling_price')
                        ->where(function ($dates) use ($today): void {
                            $dates->whereNull('discount_start_date')
                                ->orWhereDate('discount_start_date', '<=', $today);
                        })
                        ->where(function ($dates) use ($today): void {
                            $dates->whereNull('discount_end_date')
                                ->orWhereDate('discount_end_date', '>=', $today);
                        });
                });
            })
            ->with(['categories:id,name,slug', 'media'])
            ->orderBy('name');

        if ($limit !== null) {
            $query->limit(max(1, $limit));
        }

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public static function productsWithTag(ProductTag $tag, ?Branch $branch = null, ?int $limit = null): array
    {
        $limit ??= (int) config('catalog.home_products_max');

        $query = Product::query()
            ->where('status', true)
            ->whereJsonContains('tags', $tag->value)
            ->with(['categories:id,name,slug', 'media'])
            ->orderBy('name')
            ->limit(max(1, $limit));

        return $query->get()
            ->map(fn (Product $product): array => self::mapProductCard($product, $branch))
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    public static function productDetail(Product|string $product, ?Branch $branch = null): array
    {
        if (is_string($product)) {
            $product = Product::query()
                ->where('status', true)
                ->where('slug', $product)
                ->with(['categories:id,name,slug', 'media', 'variants'])
                ->first();
        } else {
            $product->loadMissing(['categories:id,name,slug', 'media', 'variants']);
        }

        if ($product === null || ! $product->status) {
            throw new NotFoundHttpException;
        }

        $primaryCategory = $product->categories->first();
        $categoryName = $primaryCategory?->name ?? 'Catalog';
        $categorySlug = $primaryCategory?->slug;
        $image = $product->imageUrl(MediaUrl::DEFAULT_FALLBACK);
        $gallery = collect([
            $image,
            ...$product->galleryUrls(),
        ])->filter()->unique()->values()->all();

        $price = $branch !== null
            ? BranchProductPriceDisplay::forBranchProduct($branch, $product)
            : BranchProductPriceDisplay::forCatalogProduct($product);

        $stock = $branch !== null ? self::stockFor($branch, $product) : 0;
        $tagBadges = collect($product->tags ?? [])
            ->map(fn (string $tag): ?string => ProductTag::tryFrom($tag)?->label())
            ->filter()
            ->values()
            ->all();

        $compareAt = $price['compare_at'] ?? null;
        $displayPrice = $price['label'] ?? 'Price unavailable';
        $reviews = ProductReview::query()
            ->where('product_id', $product->id)
            ->with(['user:id,name'])
            ->latest()
            ->get();
        $reviewCount = $reviews->count();
        $rating = $reviewCount > 0
            ? (int) round((float) $reviews->avg('rating'))
            : 0;

        return [
            'id' => $product->slug,
            'name' => $product->name,
            'description' => self::cardDescription($product),
            'category' => $categoryName,
            'categorySlug' => $categorySlug,
            'badges' => array_values(array_unique(array_filter([$categoryName, ...$tagBadges]))),
            'images' => $gallery !== [] ? $gallery : [$image],
            'price' => $displayPrice,
            'compareAtPrice' => $compareAt === null ? '' : BranchProductPriceDisplay::formatAud((float) $compareAt),
            'availability' => $branch === null
                ? 'Select a branch'
                : ($stock > 0 ? 'Available' : 'Out of stock'),
            'rating' => $rating,
            'reviewCount' => $reviewCount,
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
            'reviews' => $reviews
                ->map(fn (ProductReview $review): array => [
                    'id' => (string) $review->id,
                    'author' => $review->user?->name ?? 'Customer',
                    'date' => $review->created_at?->format('j M Y') ?? '',
                    'rating' => $review->rating,
                    'body' => $review->body,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Similar / recommended products for PDP.
     *
     * Prefer same-category affinity, then same brand, then merchandising tags
     * (best_selling → popular → on_offer → new_arrival). Backfill from
     * site-wide best sellers / popular when the category pool is thin.
     *
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public static function similarProducts(Product|string $product, ?Branch $branch = null, ?int $limit = null): array
    {
        $limit ??= (int) config('catalog.similar_products_max');
        $limit = max(1, $limit);

        if (is_string($product)) {
            $resolved = Product::query()
                ->where('status', true)
                ->where('slug', $product)
                ->with(['categories:id'])
                ->first();

            if ($resolved === null) {
                throw new NotFoundHttpException;
            }

            $product = $resolved;
        } elseif (! $product->relationLoaded('categories')) {
            $product->load('categories:id');
        }

        $categoryIds = $product->categories->pluck('id')->map(fn ($id): int => (int) $id)->values();
        $brand = is_string($product->brand) ? trim($product->brand) : '';

        $candidates = self::similarCandidatePool($product, $categoryIds);

        if ($candidates->count() < $limit) {
            $backfill = self::recommendedBackfillPool(
                excludeIds: $candidates->pluck('id')->push($product->id)->all(),
                limit: $limit * 4,
            );
            $candidates = $candidates->concat($backfill)->unique('id');
        }

        return $candidates
            ->map(fn (Product $candidate): array => [
                'product' => $candidate,
                'score' => self::recommendationScore($candidate, $categoryIds, $brand !== '' ? $brand : null),
            ])
            ->sort(function (array $left, array $right): int {
                $byScore = $right['score'] <=> $left['score'];

                if ($byScore !== 0) {
                    return $byScore;
                }

                return strcmp((string) $left['product']->name, (string) $right['product']->name);
            })
            ->take($limit)
            ->map(fn (array $row): array => self::mapProductCard($row['product'], $branch))
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, int>  $categoryIds
     * @return Collection<int, Product>
     */
    private static function similarCandidatePool(Product $product, Collection $categoryIds): Collection
    {
        $query = Product::query()
            ->where('status', true)
            ->where('id', '!=', $product->id)
            ->with(['categories:id,name,slug', 'media'])
            ->limit(100);

        if ($categoryIds->isNotEmpty()) {
            $query->whereHas(
                'categories',
                fn ($builder) => $builder->whereIn('categories.id', $categoryIds->all()),
            );
        } else {
            $query->where(function ($builder): void {
                $builder->whereJsonContains('tags', ProductTag::BestSelling->value)
                    ->orWhereJsonContains('tags', ProductTag::Popular->value);
            });
        }

        return $query->get();
    }

    /**
     * @param  list<int|string>  $excludeIds
     * @return Collection<int, Product>
     */
    private static function recommendedBackfillPool(array $excludeIds, int $limit): Collection
    {
        return Product::query()
            ->where('status', true)
            ->whereNotIn('id', $excludeIds)
            ->where(function ($builder): void {
                $builder->whereJsonContains('tags', ProductTag::BestSelling->value)
                    ->orWhereJsonContains('tags', ProductTag::Popular->value);
            })
            ->with(['categories:id,name,slug', 'media'])
            ->limit(max(1, $limit))
            ->get();
    }

    /**
     * @param  Collection<int, int>  $sourceCategoryIds
     */
    private static function recommendationScore(Product $candidate, Collection $sourceCategoryIds, ?string $brand): int
    {
        $score = 0;

        if ($sourceCategoryIds->isNotEmpty()) {
            $overlap = $sourceCategoryIds->intersect(
                $candidate->categories->pluck('id')->map(fn ($id): int => (int) $id),
            );

            if ($overlap->isNotEmpty()) {
                $score += 100;
            }
        }

        if (is_string($brand) && $brand !== '' && is_string($candidate->brand) && strcasecmp(trim($candidate->brand), $brand) === 0) {
            $score += 40;
        }

        $tags = collect($candidate->tags ?? []);

        if ($tags->contains(ProductTag::BestSelling->value)) {
            $score += 30;
        }

        if ($tags->contains(ProductTag::Popular->value)) {
            $score += 20;
        }

        if ($tags->contains(ProductTag::OnOffer->value)) {
            $score += 15;
        }

        if ($tags->contains(ProductTag::NewArrival->value)) {
            $score += 10;
        }

        if (
            $candidate->selling_price !== null
            && $candidate->regular_price !== null
            && (float) $candidate->regular_price > (float) $candidate->selling_price
        ) {
            $score += 5;
        }

        return $score;
    }

    /**
     * @return array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string, views: string}
     */
    public static function mapProductCard(Product $product, ?Branch $branch = null): array
    {
        if (! $product->relationLoaded('categories')) {
            $product->load('categories:id,name,slug');
        }

        $product->loadMissing('media');

        $categoryName = $product->categories->first()?->name ?? 'Catalog';
        $price = $branch !== null
            ? BranchProductPriceDisplay::forBranchProduct($branch, $product)
            : BranchProductPriceDisplay::forCatalogProduct($product);

        $compareAt = $price['compare_at'] ?? null;

        return [
            'id' => $product->slug,
            'name' => $product->name,
            'description' => self::cardDescription($product),
            'price' => $price['label'] ?? 'Select a branch',
            'compareAtPrice' => $compareAt === null ? '' : BranchProductPriceDisplay::formatAud((float) $compareAt),
            'category' => $categoryName,
            'image' => $product->imageUrl(MediaUrl::DEFAULT_FALLBACK),
            'views' => CompactNumber::format((int) $product->views),
        ];
    }

    /**
     * Active, in-window branch packs for the storefront Packs & Saves grid.
     *
     * @return list<array{id: string, slug: string, branchSlug: string, name: string, eyebrow: string, description: string, image: string, price: string, currency: string, savings: string|null, stockLeft: int}>
     */
    public static function packs(?Branch $branch = null, ?string $search = null, ?string $sort = null): array
    {
        $query = self::activePacksQuery($branch);

        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhereHas('items.product', fn (Builder $product) => $product->where('name', 'like', '%'.$search.'%'));
            });
        }

        match ($sort) {
            'price_asc' => $query->orderBy('pack_price'),
            'price_desc' => $query->orderByDesc('pack_price'),
            'newest' => $query->orderByDesc('created_at'),
            default => $query->orderByRaw('(subtotal - pack_price) desc'),
        };

        return $query->orderBy('id')
            ->get()
            ->map(fn (Pack $pack): array => self::mapPackCard($pack))
            ->values()
            ->all();
    }

    /**
     * The platform-highlighted active pack, falling back to the active pack
     * with the biggest saving, shown as the flagship offer.
     *
     * @return array{id: string, slug: string, branchSlug: string, name: string, eyebrow: string, description: string, image: string, price: string, currency: string, savings: string|null, stockLeft: int, regularPrice: string, discountPercent: int, branchName: string, items: list<array{name: string, quantity: int}>}|null
     */
    public static function featuredPack(?Branch $branch = null): ?array
    {
        $pack = self::activePacksQuery($branch)
            ->whereNotNull('highlighted_at')
            ->latest('highlighted_at')
            ->first()
            ?? self::activePacksQuery($branch)
                ->orderByRaw('(subtotal - pack_price) desc')
                ->orderBy('id')
                ->first();

        if ($pack === null) {
            return null;
        }

        return [
            ...self::mapPackCard($pack),
            ...self::packOffer($pack),
        ];
    }

    /**
     * An active, in-window pack addressed by its branch slug and pack slug.
     */
    public static function findActivePack(string $branchSlug, string $packSlug): ?Pack
    {
        return self::activePacksQuery(null)
            ->where('slug', $packSlug)
            ->whereHas('branch', fn (Builder $builder) => $builder->where('slug', $branchSlug))
            ->with(['media', 'items.product:id,name,slug', 'items.product.media'])
            ->first();
    }

    /**
     * Storefront pack detail payload.
     *
     * @return array{id: string, slug: string, branchSlug: string, name: string, eyebrow: string, description: string, image: string, price: string, currency: string, savings: string|null, stockLeft: int, regularPrice: string, discountPercent: int, branchName: string, items: list<array{name: string, quantity: int, image: string}>, images: list<string>, productCount: int, totalUnits: int, startsAt: string|null, endsAt: string|null}
     */
    public static function packDetail(Pack $pack): array
    {
        $images = collect([$pack->imageUrl()])
            ->merge($pack->galleryUrls())
            ->filter(fn (?string $url): bool => is_string($url) && $url !== '')
            ->unique()
            ->values()
            ->all();

        return [
            ...self::mapPackCard($pack),
            ...self::packOffer($pack),
            'items' => $pack->items
                ->map(fn (PackItem $item): array => [
                    'name' => $item->product->name ?? '',
                    'quantity' => $item->quantity,
                    'image' => $item->product?->imageUrl() ?? '',
                ])
                ->values()
                ->all(),
            'images' => $images,
            'productCount' => $pack->items->count(),
            'totalUnits' => (int) $pack->items->sum('quantity'),
            'startsAt' => $pack->starts_at?->format('j M Y'),
            'endsAt' => $pack->ends_at?->format('j M Y'),
        ];
    }

    /**
     * Other active packs, same branch first, then biggest saving.
     *
     * @return list<array{id: string, slug: string, branchSlug: string, name: string, eyebrow: string, description: string, image: string, price: string, currency: string, savings: string|null, stockLeft: int}>
     */
    public static function relatedPacks(Pack $pack, int $limit = 4): array
    {
        return self::activePacksQuery(null)
            ->whereKeyNot($pack->id)
            ->orderByRaw('case when branch_id = ? then 0 else 1 end', [$pack->branch_id])
            ->orderByRaw('(subtotal - pack_price) desc')
            ->orderBy('id')
            ->limit($limit)
            ->get()
            ->map(fn (Pack $related): array => self::mapPackCard($related))
            ->values()
            ->all();
    }

    public static function activePackCount(Branch $branch): int
    {
        return self::activePacksQuery($branch)->count();
    }

    /**
     * @return array{id: string, slug: string, branchSlug: string, name: string, eyebrow: string, description: string, image: string, price: string, currency: string, savings: string|null, stockLeft: int}
     */
    public static function mapPackCard(Pack $pack): array
    {
        $savings = (float) $pack->subtotal - (float) $pack->pack_price;
        $units = (int) $pack->items->sum('quantity');
        $branchSlug = (string) ($pack->branch->slug ?? $pack->branch_id);

        return [
            'id' => $branchSlug.':'.$pack->slug,
            'slug' => $pack->slug,
            'branchSlug' => $branchSlug,
            'name' => $pack->name,
            'eyebrow' => $units > 0 ? $units.'-Pack Bundle' : 'Pack Bundle',
            'description' => (string) $pack->description,
            'image' => $pack->imageUrl() ?? '',
            'price' => number_format((float) $pack->pack_price, 2, '.', ''),
            'currency' => 'AUD',
            'savings' => $savings > 0 ? number_format($savings, 2, '.', '') : null,
            'stockLeft' => $pack->quantity,
        ];
    }

    /**
     * @return array{regularPrice: string, discountPercent: int, branchName: string, items: list<array{name: string, quantity: int}>}
     */
    private static function packOffer(Pack $pack): array
    {
        $subtotal = (float) $pack->subtotal;
        $savings = max(0, $subtotal - (float) $pack->pack_price);

        return [
            'regularPrice' => number_format($subtotal, 2, '.', ''),
            'discountPercent' => $subtotal > 0 ? (int) round($savings / $subtotal * 100) : 0,
            'branchName' => $pack->branch->name ?? '',
            'items' => $pack->items
                ->map(fn (PackItem $item): array => [
                    'name' => $item->product->name ?? '',
                    'quantity' => $item->quantity,
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @return Builder<Pack>
     */
    private static function activePacksQuery(?Branch $branch): Builder
    {
        $today = now()->toDateString();

        return Pack::query()
            ->where('status', true)
            ->where('quantity', '>', 0)
            ->where(fn (Builder $dates) => $dates->whereNull('starts_at')->orWhereDate('starts_at', '<=', $today))
            ->where(fn (Builder $dates) => $dates->whereNull('ends_at')->orWhereDate('ends_at', '>=', $today))
            ->whereHas('branch', fn (Builder $builder) => $builder->where('status', true))
            ->when($branch !== null, fn (Builder $builder) => $builder->where('branch_id', $branch?->id))
            ->with(['branch:id,name,slug', 'media', 'items.product:id,name']);
    }

    private static function cardDescription(Product $product): string
    {
        $packSummary = $product->packSummary();

        if (is_string($packSummary) && $packSummary !== '') {
            return $packSummary;
        }

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
            ->sum('quantity');
    }
}
