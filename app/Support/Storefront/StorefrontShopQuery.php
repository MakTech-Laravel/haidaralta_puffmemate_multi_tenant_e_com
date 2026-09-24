<?php

namespace App\Support\Storefront;

use App\Enums\ProductTag;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\PriceRangeService;
use App\Support\BranchProductPriceDisplay;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Throwable;

class StorefrontShopQuery
{
    public const DEFAULT_LIMIT = 96;

    public const SORTS = [
        'name_asc',
        'name_desc',
        'newest',
        'price_asc',
        'price_desc',
    ];

    public function __construct(
        private readonly PriceRangeService $priceRanges,
    ) {}

    /**
     * @return array{
     *     products: list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>,
     *     filters: array{
     *         search: string,
     *         sort: string|null,
     *         minPrice: float|null,
     *         maxPrice: float|null,
     *         categories: list<string>,
     *         tags: list<string>
     *     },
     *     filterOptions: array{
     *         categories: list<array{slug: string, name: string}>,
     *         tags: list<array{value: string, label: string}>,
     *         priceRanges: list<array{slug: string, label: string, min: float|null, max: float|null}>,
     *         sliderMin: int,
     *         sliderMax: int
     *     }
     * }
     */
    public function fromRequest(Request $request): array
    {
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString();
        $sort = in_array($sort, self::SORTS, true) ? $sort : null;

        $minPrice = $this->nullableFloat($request->input('min_price'));
        $maxPrice = $this->nullableFloat($request->input('max_price'));

        $categorySlugs = $this->stringList($request->input('categories'));
        $tagValues = array_values(array_intersect(
            $this->stringList($request->input('tags')),
            ProductTag::values(),
        ));

        $products = $this->products(
            search: $search !== '' ? $search : null,
            sort: $sort,
            minPrice: $minPrice,
            maxPrice: $maxPrice,
            categorySlugs: $categorySlugs,
            tags: $tagValues,
        );

        return [
            'products' => $products,
            'filters' => [
                'search' => $search,
                'sort' => $sort,
                'minPrice' => $minPrice,
                'maxPrice' => $maxPrice,
                'categories' => $categorySlugs,
                'tags' => $tagValues,
            ],
            'filterOptions' => $this->filterOptions(),
        ];
    }

    /**
     * @param  list<string>  $categorySlugs
     * @param  list<string>  $tags
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    public function products(
        ?string $search = null,
        ?string $sort = null,
        ?float $minPrice = null,
        ?float $maxPrice = null,
        array $categorySlugs = [],
        array $tags = [],
        int $limit = self::DEFAULT_LIMIT,
    ): array {
        $categoryIds = $this->resolveCategoryIds($categorySlugs);
        $sort = in_array((string) $sort, self::SORTS, true) ? $sort : 'name_asc';

        $scoutIds = $this->searchIdsViaScout($search, $limit * 2);

        $query = Product::query()
            ->where('status', true)
            ->with(['categories:id,name,slug', 'images']);

        if ($scoutIds !== null) {
            if ($scoutIds === []) {
                return [];
            }

            $query->whereIn('id', $scoutIds);
        } elseif (is_string($search) && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%');
            });
        }

        if ($categoryIds !== []) {
            $query->whereHas(
                'categories',
                fn (Builder $builder) => $builder->whereIn('categories.id', $categoryIds),
            );
        }

        if ($tags !== []) {
            $query->where(function (Builder $builder) use ($tags): void {
                foreach ($tags as $index => $tag) {
                    $method = $index === 0 ? 'whereJsonContains' : 'orWhereJsonContains';
                    $builder->{$method}('tags', $tag);
                }
            });
        }

        $candidates = $query->limit(max($limit * 3, 150))->get();

        $filtered = $candidates
            ->filter(fn (Product $product): bool => $this->matchesPrice($product, $minPrice, $maxPrice))
            ->values();

        $sorted = $this->sortProducts($filtered, $sort, $scoutIds);

        return $sorted
            ->take($limit)
            ->map(fn (Product $product): array => StorefrontCatalog::mapProductCard($product))
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     categories: list<array{slug: string, name: string}>,
     *     tags: list<array{value: string, label: string}>,
     *     priceRanges: list<array{slug: string, label: string, min: float|null, max: float|null}>,
     *     sliderMin: int,
     *     sliderMax: int
     * }
     */
    public function filterOptions(): array
    {
        $categories = Category::query()
            ->where('status', true)
            ->whereNull('parent_id')
            ->orderByRaw('CASE WHEN nav_sort IS NULL THEN 1 ELSE 0 END')
            ->orderBy('nav_sort')
            ->orderBy('name')
            ->get(['name', 'slug'])
            ->map(fn (Category $category): array => [
                'slug' => $category->slug,
                'name' => $category->name,
            ])
            ->values()
            ->all();

        $tags = collect(ProductTag::cases())
            ->map(fn (ProductTag $tag): array => [
                'value' => $tag->value,
                'label' => $tag->label(),
            ])
            ->values()
            ->all();

        $bounds = $this->priceRanges->sliderBounds();

        return [
            'categories' => $categories,
            'tags' => $tags,
            'priceRanges' => $this->priceRanges->activeForStorefront(),
            'sliderMin' => $bounds['min'],
            'sliderMax' => $bounds['max'],
        ];
    }

    /**
     * @return list<int>|null null means Scout unavailable / skip ID constraint
     */
    private function searchIdsViaScout(?string $search, int $limit): ?array
    {
        if (! is_string($search) || $search === '') {
            return null;
        }

        try {
            return Product::search($search)
                ->take($limit)
                ->keys()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function resolveCategoryIds(array $slugs): array
    {
        if ($slugs === []) {
            return [];
        }

        return Category::query()
            ->where('status', true)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();
    }

    private function matchesPrice(Product $product, ?float $minPrice, ?float $maxPrice): bool
    {
        if ($minPrice === null && $maxPrice === null) {
            return true;
        }

        if ($product->selling_price === null) {
            return false;
        }

        $effective = BranchProductPriceDisplay::catalogEffectiveAmount($product);

        if ($minPrice !== null && $effective < $minPrice) {
            return false;
        }

        if ($maxPrice !== null && $effective > $maxPrice) {
            return false;
        }

        return true;
    }

    /**
     * @param  Collection<int, Product>  $products
     * @param  list<int>|null  $scoutIds
     * @return Collection<int, Product>
     */
    private function sortProducts(Collection $products, string $sort, ?array $scoutIds): Collection
    {
        return match ($sort) {
            'name_desc' => $products->sortByDesc(fn (Product $product): string => $product->name)->values(),
            'newest' => $products->sortByDesc(fn (Product $product): int => $product->created_at?->getTimestamp() ?? 0)->values(),
            'price_asc' => $products->sortBy(fn (Product $product): float => $this->effectiveOrMax($product))->values(),
            'price_desc' => $products->sortByDesc(fn (Product $product): float => $this->effectiveOrMax($product))->values(),
            default => $scoutIds === null
                ? $products->sortBy(fn (Product $product): string => $product->name)->values()
                : $products->sortBy(function (Product $product) use ($scoutIds): int {
                    $position = array_search($product->id, $scoutIds, true);

                    return $position === false ? PHP_INT_MAX : $position;
                })->values(),
        };
    }

    private function effectiveOrMax(Product $product): float
    {
        if ($product->selling_price === null) {
            return PHP_FLOAT_MAX;
        }

        return BranchProductPriceDisplay::catalogEffectiveAmount($product);
    }

    /**
     * @return list<string>
     */
    private function stringList(mixed $value): array
    {
        if (is_string($value) && $value !== '') {
            return [$value];
        }

        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn (mixed $item): bool => is_string($item) && $item !== '')
            ->map(fn (string $item): string => trim($item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function nullableFloat(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        return (float) $value;
    }
}
