<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use App\Support\BranchProductPriceDisplay;
use App\Support\CategoryOptions;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends AbstractDataTable<Product>
 */
class BranchProductListingDataTable extends AbstractDataTable
{
    /** @var Collection<int, Inventory> */
    protected Collection $inventories;

    /** @var Collection<int, BranchProductPrice> */
    protected Collection $prices;

    public function __construct(
        Request $request,
        protected Branch $branch,
        protected bool $includeImage = false,
    ) {
        parent::__construct($request);

        $this->inventories = collect();
        $this->prices = collect();
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch, bool $includeImage = false): array
    {
        return (new static($request, $branch, $includeImage))->toArray();
    }

    /**
     * @return class-string<Product>
     */
    protected function modelClass(): string
    {
        return Product::class;
    }

    /**
     * @return Builder<Product>
     */
    protected function baseQuery(): Builder
    {
        return Product::query()->with('categories:id,name,slug');
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'slug', 'sku'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'sku',
            'status',
            'created_at',
        ];
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        $branchId = $this->branch->id;

        return [
            AllowedFilter::callback('category_id', function (Builder $query, mixed $value): void {
                $ids = CategoryOptions::normalizeIds($value);

                if ($ids === []) {
                    return;
                }

                $query->whereHas(
                    'categories',
                    fn (Builder $categories): Builder => $categories->whereIn('categories.id', $ids),
                );
            }),
            StatusBooleanFilter::make('status'),
            AllowedFilter::callback('missing_price', function (Builder $query, mixed $value) use ($branchId): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $normalized = is_bool($value)
                    ? ($value ? '1' : '0')
                    : strtolower((string) $value);

                if (! in_array($normalized, ['1', 'true', 'yes'], true)) {
                    return;
                }

                if (! BranchProductPriceDisplay::usesBranchPricing()) {
                    $query->whereNull('selling_price');

                    return;
                }

                $query->whereDoesntHave(
                    'branchPrices',
                    fn (Builder $prices): Builder => $prices
                        ->where('branch_id', $branchId)
                        ->whereNull('variant_id')
                        ->where('status', true),
                );
            }),
            $this->searchFilter(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $ids = CategoryOptions::normalizeIds(
            $this->normalizedFilters()['category_id'] ?? null,
        );

        return [
            'categories' => CategoryOptions::selected($ids),
            'missing_price' => [
                ['value' => 'all', 'label' => 'All prices'],
                ['value' => '1', 'label' => 'Missing price'],
            ],
            'status' => [
                ['value' => 'all', 'label' => 'All catalog'],
                ['value' => '1', 'label' => 'Active'],
                ['value' => '0', 'label' => 'Inactive'],
            ],
        ];
    }

    /**
     * @return array{
     *     data: list<array<string, mixed>>,
     *     meta: array{current_page: int, last_page: int, per_page: int, total: int, from: ?int, to: ?int},
     *     links: list<array{url: ?string, label: string, active: bool}>,
     *     filters: array<string, mixed>,
     *     filterOptions: array<string, mixed>,
     *     sort: ?string,
     *     per_page: int
     * }
     */
    public function toArray(): array
    {
        $perPage = $this->resolvePerPage();
        $query = $this->baseQuery();

        $this->applyScoutSearchConstraint($query);

        $paginator = QueryBuilder::for($query, $this->request)
            ->allowedFilters(...$this->allowedFilters())
            ->allowedSorts(...$this->allowedSorts())
            ->defaultSort($this->defaultSort())
            ->paginate($perPage)
            ->appends($this->request->query());

        $this->loadBranchContext($paginator->getCollection()->pluck('id'));

        $rows = [];

        foreach ($paginator->getCollection() as $model) {
            $rows[] = $this->mapRow($model);
        }

        return $this->formatPaginator($paginator, $rows);
    }

    /**
     * @param  Collection<int, int|string>|list<int|string>  $productIds
     */
    protected function loadBranchContext(Collection|array $productIds): void
    {
        $ids = collect($productIds)->map(fn ($id): int => (int) $id)->values();

        if ($ids->isEmpty()) {
            $this->inventories = collect();
            $this->prices = collect();

            return;
        }

        $this->inventories = Inventory::query()
            ->where('branch_id', $this->branch->id)
            ->whereIn('product_id', $ids)
            ->whereNull('variant_id')
            ->get()
            ->keyBy('product_id');

        $this->prices = BranchProductPrice::query()
            ->where('branch_id', $this->branch->id)
            ->whereIn('product_id', $ids)
            ->whereNull('variant_id')
            ->where('status', true)
            ->get()
            ->keyBy('product_id');
    }

    /**
     * @param  Product  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Product $model */
        /** @var Inventory|null $inventory */
        $inventory = $this->inventories->get($model->id);
        /** @var BranchProductPrice|null $price */
        $price = $this->prices->get($model->id);
        $priceDisplay = BranchProductPriceDisplay::listDisplayForProductContext($price, $model);

        $row = [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'sku' => $model->sku,
            'category_ids' => $model->categories->pluck('id')->map(fn ($id): int => (int) $id)->values()->all(),
            'category_name' => $model->categoryNamesLabel(),
            'categories' => $model->categories
                ->map(fn ($category): array => [
                    'id' => $category->id,
                    'name' => $category->name,
                    'slug' => $category->slug,
                ])
                ->values()
                ->all(),
            'category' => $model->categories->first()
                ? [
                    'id' => $model->categories->first()->id,
                    'name' => $model->categories->first()->name,
                    'slug' => $model->categories->first()->slug,
                ]
                : null,
            'status' => (bool) $model->status,
            'color_code' => $model->color_code,
            'quantity' => $inventory?->quantity ?? 0,
            'selling_price' => $priceDisplay['selling_price'],
            'effective_price' => $priceDisplay['effective_price'],
            'regular_price' => $priceDisplay['regular_price'],
            'has_discount' => $priceDisplay['has_discount'],
            'has_compare_at' => $priceDisplay['has_compare_at'],
            'discount_type' => $priceDisplay['discount_type'],
            'discount_price' => $priceDisplay['discount_price'],
        ];

        if ($this->includeImage) {
            $row['image_url'] = $model->imageUrl();
        }

        return $row;
    }
}
