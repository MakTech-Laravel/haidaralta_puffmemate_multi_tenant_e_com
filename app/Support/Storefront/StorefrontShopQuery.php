<?php

namespace App\Support\Storefront;

use App\Enums\ProductTag;
use App\Models\Category;
use App\Models\Product;
use App\Services\Catalog\PriceRangeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Laravel\Scout\Builder as ScoutBuilder;
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
     *     }
     * }
     */
    public function fromRequest(Request $request, ?string $lockedCategorySlug = null): array
    {
        $search = $request->string('search')->trim()->toString();
        $sort = $request->string('sort')->trim()->toString();
        $sort = in_array($sort, self::SORTS, true) ? $sort : null;

        $minPrice = $this->nullableFloat($request->input('min_price'));
        $maxPrice = $this->nullableFloat($request->input('max_price'));

        $categorySlugs = $lockedCategorySlug !== null && $lockedCategorySlug !== ''
            ? [$lockedCategorySlug]
            : $this->stringList($request->input('categories'));
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
            includeDescendantCategories: $lockedCategorySlug !== null && $lockedCategorySlug !== '',
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
        bool $includeDescendantCategories = false,
    ): array {
        $categoryIds = $this->resolveCategoryIds($categorySlugs, $includeDescendantCategories);
        $sort = in_array((string) $sort, self::SORTS, true) ? $sort : null;

        $scoutIds = $this->searchIdsViaScout(
            $search,
            $sort,
            $minPrice,
            $maxPrice,
            $categoryIds,
            $tags,
            $limit,
        );

        if ($scoutIds !== null) {
            return $this->cardsInOrder($scoutIds);
        }

        $query = Product::query()
            ->where('status', true)
            ->with(['categories:id,name,slug']);

        if (is_string($search) && $search !== '') {
            $query->where(function (Builder $builder) use ($search): void {
                $builder->where('name', 'like', '%'.$search.'%')
                    ->orWhere('brand', 'like', '%'.$search.'%')
                    ->orWhere('sku', 'like', '%'.$search.'%')
                    ->orWhere('description', 'like', '%'.$search.'%')
                    ->orWhere('pack_label', 'like', '%'.$search.'%');
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

        $this->constrainEffectivePrice($query, $minPrice, $maxPrice);
        $this->orderEloquent($query, $sort);

        return $query
            ->limit($limit)
            ->get()
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
     * @param  list<int>  $categoryIds
     * @param  list<string>  $tags
     * @return list<int>|null null means Scout is not the engine or the request failed
     */
    private function searchIdsViaScout(
        ?string $search,
        ?string $sort,
        ?float $minPrice,
        ?float $maxPrice,
        array $categoryIds,
        array $tags,
        int $limit,
    ): ?array {
        if (config('scout.driver') !== 'meilisearch') {
            return null;
        }

        try {
            $builder = Product::search($search ?? '')
                ->where('status', true)
                ->take($limit);

            if ($categoryIds !== []) {
                $builder->whereIn('category_ids', $categoryIds);
            }

            if ($tags !== []) {
                $builder->whereIn('tags', $tags);
            }

            if ($minPrice !== null) {
                $builder->where('effective_price', '>=', $minPrice);
            }

            if ($maxPrice !== null) {
                $builder->where('effective_price', '<=', $maxPrice);
            }

            $this->orderScout($builder, $sort, $search);

            return $builder->keys()
                ->map(fn ($id): int => (int) $id)
                ->values()
                ->all();
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  list<int>  $ids
     * @return list<array{id: string, name: string, description: string, price: string, compareAtPrice: string, category: string, image: string}>
     */
    private function cardsInOrder(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        $order = array_flip($ids);

        return Product::query()
            ->where('status', true)
            ->whereIn('id', $ids)
            ->with(['categories:id,name,slug'])
            ->get()
            ->sortBy(fn (Product $product): int => $order[$product->id] ?? PHP_INT_MAX)
            ->map(fn (Product $product): array => StorefrontCatalog::mapProductCard($product))
            ->values()
            ->all();
    }

    private function orderScout(ScoutBuilder $builder, ?string $sort, ?string $search): void
    {
        if ($sort === null && is_string($search) && $search !== '') {
            return;
        }

        match ($sort) {
            'name_desc' => $builder->orderBy('name', 'desc'),
            'newest' => $builder->orderBy('created_at', 'desc'),
            'price_asc' => $builder->orderBy('effective_price', 'asc'),
            'price_desc' => $builder->orderBy('effective_price', 'desc'),
            default => $builder->orderBy('name', 'asc'),
        };
    }

    private function orderEloquent(Builder $query, ?string $sort): void
    {
        $price = $this->effectivePriceSql($query);

        match ($sort) {
            'name_desc' => $query->orderByDesc('name'),
            'newest' => $query->orderByDesc('created_at'),
            'price_asc' => $query->orderByRaw('('.$price.') asc'),
            'price_desc' => $query->orderByRaw('('.$price.') desc'),
            default => $query->orderBy('name'),
        };
    }

    private function constrainEffectivePrice(Builder $query, ?float $minPrice, ?float $maxPrice): void
    {
        if ($minPrice === null && $maxPrice === null) {
            return;
        }

        $price = $this->effectivePriceSql($query);
        $query->whereNotNull('selling_price');

        if ($minPrice !== null) {
            $query->whereRaw('('.$price.') >= '.$this->sqlNumber($minPrice));
        }

        if ($maxPrice !== null) {
            $query->whereRaw('('.$price.') <= '.$this->sqlNumber($maxPrice));
        }

    }

    private function sqlNumber(float $amount): string
    {
        return rtrim(rtrim(number_format($amount, 2, '.', ''), '0'), '.');
    }

    private function effectivePriceSql(Builder $query): string
    {
        $isPgsql = $query->getConnection()->getDriverName() === 'pgsql';
        $today = $isPgsql ? 'CURRENT_DATE' : "date('now')";
        $floor = $isPgsql ? 'GREATEST' : 'MAX';

        return "CASE WHEN selling_price IS NULL THEN NULL WHEN discount_type = 'fixed' AND (discount_start_date IS NULL OR discount_start_date <= {$today}) AND (discount_end_date IS NULL OR discount_end_date >= {$today}) THEN {$floor}(0, selling_price - COALESCE(discount_price, 0)) WHEN discount_type = 'percent' AND (discount_start_date IS NULL OR discount_start_date <= {$today}) AND (discount_end_date IS NULL OR discount_end_date >= {$today}) THEN {$floor}(0, ROUND(selling_price * (1 - (COALESCE(discount_price, 0) / 100)), 2)) ELSE selling_price END";
    }

    /**
     * @param  list<string>  $slugs
     * @return list<int>
     */
    private function resolveCategoryIds(array $slugs, bool $includeDescendants = false): array
    {
        if ($slugs === []) {
            return [];
        }

        $ids = Category::query()
            ->where('status', true)
            ->whereIn('slug', $slugs)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->values()
            ->all();

        if (! $includeDescendants || $ids === []) {
            return $ids;
        }

        $childIds = Category::query()
            ->where('status', true)
            ->whereIn('parent_id', $ids)
            ->pluck('id')
            ->map(fn ($id): int => (int) $id)
            ->all();

        return array_values(array_unique([...$ids, ...$childIds]));
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
