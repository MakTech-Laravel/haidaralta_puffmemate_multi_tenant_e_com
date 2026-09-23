<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Models\BranchProductPrice;
use App\Models\Inventory;
use App\Models\Product;
use App\Support\BranchProductPriceDisplay;
use App\Support\ProductManageRedirect;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\QueryBuilder;

/**
 * @extends AbstractDataTable<Branch>
 */
class ProductBranchInventoryDataTable extends AbstractDataTable
{
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

        $rows = [];

        foreach ($paginator->getCollection() as $model) {
            $rows[] = $this->mapRow($model);
        }

        return $this->formatPaginator($paginator, $rows);
    }

    /**
     * @param  Collection<int, int|string>|list<int|string>  $branchIds
     */
    protected function loadProductContext(Collection|array $branchIds): void
    {
        $ids = collect($branchIds)->map(fn ($id): int => (int) $id)->values();

        if ($ids->isEmpty()) {
            $this->inventories = collect();
            $this->prices = collect();

            return;
        }

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
        $priceDisplay = BranchProductPriceDisplay::listDisplay($price);

        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'code' => $model->code,
            'branch_active' => (bool) $model->status,
            'quantity' => $inventory?->quantity ?? 0,
            'selling_price' => $priceDisplay['selling_price'],
            'effective_price' => $priceDisplay['effective_price'],
            'regular_price' => $priceDisplay['regular_price'],
            'has_discount' => $priceDisplay['has_discount'],
            'has_compare_at' => $priceDisplay['has_compare_at'],
            'discount_type' => $priceDisplay['discount_type'],
            'discount_price' => $priceDisplay['discount_price'],
            'manage_url' => ProductManageRedirect::platformUrl($model, $this->product),
        ];
    }
}
