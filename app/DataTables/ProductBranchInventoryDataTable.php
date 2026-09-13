<?php

namespace App\DataTables;

use App\Enums\DiscountType;
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

class ProductBranchInventoryDataTable extends AbstractDataTable
{
    /** @var array<int, bool> */
    protected array $enabledMap = [];

    /** @var Collection<int, Inventory> */
    protected Collection $inventories;

    /** @var Collection<int, BranchProductPrice> */
    protected Collection $prices;

    public function __construct(
        Request $request,
        protected Product $product,
    ) {
        parent::__construct($request);

        $this->inventories = collect();
        $this->prices = collect();
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForProduct(Request $request, Product $product): array
    {
        return (new static($request, $product))->toArray();
    }

    /**
     * @return class-string<Branch>
     */
    protected function modelClass(): string
    {
        return Branch::class;
    }

    /**
     * @return Builder<Branch>
     */
    protected function baseQuery(): Builder
    {
        return Branch::query();
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'slug', 'code'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'slug',
            'code',
            'status',
            'created_at',
        ];
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        $productId = $this->product->id;

        return [
            AllowedFilter::callback('enabled', function (Builder $query, mixed $value) use ($productId): void {
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
                            ->where('product_id', $productId)
                            ->where('status', true),
                    );

                    return;
                }

                if (in_array($normalized, ['0', 'false', 'no', 'disabled'], true)) {
                    $query->whereDoesntHave(
                        'branchProducts',
                        fn (Builder $branchProducts): Builder => $branchProducts
                            ->where('product_id', $productId)
                            ->where('status', true),
                    );
                }
            }),
            AllowedFilter::callback('low_stock', function (Builder $query, mixed $value) use ($productId): void {
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
                        ->where('product_id', $productId)
                        ->whereNull('variant_id')
                        ->whereColumn('available_quantity', '<=', 'minimum_stock'),
                );
            }),
            AllowedFilter::callback('missing_price', function (Builder $query, mixed $value) use ($productId): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $normalized = is_bool($value)
                    ? ($value ? '1' : '0')
                    : strtolower((string) $value);

                if (! in_array($normalized, ['1', 'true', 'yes'], true)) {
                    return;
                }

                $query->whereDoesntHave(
                    'productPrices',
                    fn (Builder $prices): Builder => $prices
                        ->where('product_id', $productId)
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
            'missing_price' => [
                ['value' => 'all', 'label' => 'All prices'],
                ['value' => '1', 'label' => 'Missing price'],
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

        $this->loadProductContext($paginator->getCollection()->pluck('id'));

        $paginator->setCollection(
            $paginator->getCollection()->map(fn (Model $model): array => $this->mapRow($model)),
        );

        return $this->formatPaginator($paginator);
    }

    /**
     * @param  Collection<int, int|string>|list<int|string>  $branchIds
     */
    protected function loadProductContext(Collection|array $branchIds): void
    {
        $ids = collect($branchIds)->map(fn ($id): int => (int) $id)->values();

        if ($ids->isEmpty()) {
            $this->enabledMap = [];
            $this->inventories = collect();
            $this->prices = collect();

            return;
        }

        $this->enabledMap = BranchProduct::query()
            ->where('product_id', $this->product->id)
            ->whereIn('branch_id', $ids)
            ->pluck('status', 'branch_id')
            ->map(fn ($status): bool => (bool) $status)
            ->all();

        $this->inventories = Inventory::query()
            ->where('product_id', $this->product->id)
            ->whereIn('branch_id', $ids)
            ->whereNull('variant_id')
            ->get()
            ->keyBy('branch_id');

        $this->prices = BranchProductPrice::query()
            ->where('product_id', $this->product->id)
            ->whereIn('branch_id', $ids)
            ->whereNull('variant_id')
            ->where('status', true)
            ->get()
            ->keyBy('branch_id');
    }

    /**
     * @param  Branch  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Branch $model */
        /** @var Inventory|null $inventory */
        $inventory = $this->inventories->get($model->id);
        /** @var BranchProductPrice|null $price */
        $price = $this->prices->get($model->id);

        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'code' => $model->code,
            'branch_active' => (bool) $model->status,
            'enabled' => (bool) ($this->enabledMap[$model->id] ?? false),
            'quantity' => $inventory?->quantity,
            'available_quantity' => $inventory?->available_quantity,
            'minimum_stock' => $inventory?->minimum_stock,
            'is_low_stock' => $inventory?->isLowStock() ?? false,
            'selling_price' => $price === null ? null : (string) $price->selling_price,
            'discount_type' => $price?->discount_type?->value ?? DiscountType::None->value,
            'discount_price' => $price?->discount_price === null
                ? null
                : (string) $price->discount_price,
            'manage_url' => route('platform.branches.products.show', [
                'branch' => $model->slug,
                'product' => $this->product->slug,
            ]),
        ];
    }
}
