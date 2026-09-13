<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Models\BranchProduct;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

class BranchProductListingDataTable extends AbstractDataTable
{
    /** @var array<int, bool> */
    protected array $enabledMap = [];

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
            AllowedFilter::callback('enabled', function (Builder $query, mixed $value) use ($branchId): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $normalized = is_bool($value)
                    ? ($value ? '1' : '0')
                    : strtolower((string) $value);

                if (in_array($normalized, ['1', 'true', 'yes', 'enabled'], true)) {
                    $query->whereHas(
                        'branchProducts',
                        fn (Builder $branchProducts): Builder => $branchProducts
                            ->where('branch_id', $branchId)
                            ->where('status', true),
                    );

                    return;
                }

                if (in_array($normalized, ['0', 'false', 'no', 'disabled'], true)) {
                    $query->whereDoesntHave(
                        'branchProducts',
                        fn (Builder $branchProducts): Builder => $branchProducts
                            ->where('branch_id', $branchId)
                            ->where('status', true),
                    );
                }
            }),
            AllowedFilter::callback('low_stock', function (Builder $query, mixed $value) use ($branchId): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $normalized = is_bool($value)
                    ? ($value ? '1' : '0')
                    : strtolower((string) $value);

                if (! in_array($normalized, ['1', 'true', 'yes'], true)) {
                    return;
                }

                $query->whereHas(
                    'inventories',
                    fn (Builder $inventories): Builder => $inventories
                        ->where('branch_id', $branchId)
                        ->whereNull('variant_id')
                        ->whereColumn('available_quantity', '<=', 'minimum_stock'),
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
        return [
            'enabled' => [
                ['value' => 'all', 'label' => 'All enablement'],
                ['value' => '1', 'label' => 'Enabled'],
                ['value' => '0', 'label' => 'Disabled'],
            ],
            'low_stock' => [
                ['value' => 'all', 'label' => 'All stock'],
                ['value' => '1', 'label' => 'Low stock'],
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

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Model $model): array => $this->mapRow($model)),
        );

        return $this->formatPaginator($paginator);
    }

    /**
     * @param  Collection<int, int|string>|list<int|string>  $productIds
     */
    protected function loadBranchContext(Collection|array $productIds): void
    {
        $ids = collect($productIds)->map(fn ($id): int => (int) $id)->values();

        if ($ids->isEmpty()) {
            $this->enabledMap = [];
            $this->inventories = collect();
            $this->prices = collect();

            return;
        }

        $this->enabledMap = BranchProduct::query()
            ->where('branch_id', $this->branch->id)
            ->whereIn('product_id', $ids)
            ->pluck('status', 'product_id')
            ->map(fn ($status): bool => (bool) $status)
            ->all();

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
            'enabled' => (bool) ($this->enabledMap[$model->id] ?? false),
            'quantity' => $inventory?->quantity ?? 0,
            'available_quantity' => $inventory?->available_quantity ?? 0,
            'minimum_stock' => $inventory?->minimum_stock ?? 0,
            'is_low_stock' => $inventory?->isLowStock() ?? false,
            'selling_price' => $price?->selling_price,
        ];

        if ($this->includeImage) {
            $row['image_url'] = $model->imageUrl();
            $row['discount_price'] = $price?->discount_price;
        }

        return $row;
    }
}
