<?php

namespace App\DataTables;

use App\Enums\InventoryTransactionType;
use App\Models\Branch;
use App\Models\InventoryTransaction;
use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

class InventoryTransactionDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected ?int $scopedBranchId = null,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeFor(Request $request, ?int $scopedBranchId = null): array
    {
        return (new static($request, $scopedBranchId))->toArray();
    }

    /**
     * @return class-string<InventoryTransaction>
     */
    protected function modelClass(): string
    {
        return InventoryTransaction::class;
    }

    /**
     * @return Builder<InventoryTransaction>
     */
    protected function baseQuery(): Builder
    {
        $query = InventoryTransaction::query()
            ->with([
                'branch:id,name,slug',
                'product:id,name,slug,sku',
                'creator:id,name',
            ]);

        if ($this->scopedBranchId !== null) {
            $query->where('branch_id', $this->scopedBranchId);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return [
            'type',
            'quantity',
            'created_at',
            'before_quantity',
            'after_quantity',
        ];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                $term = trim((string) $value);

                if ($term === '') {
                    return;
                }

                $query->where(function (Builder $builder) use ($term): void {
                    $builder->where('type', 'like', '%'.$term.'%')
                        ->orWhereHas(
                            'product',
                            fn (Builder $products): Builder => $products
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('sku', 'like', '%'.$term.'%')
                                ->orWhere('slug', 'like', '%'.$term.'%'),
                        )
                        ->orWhereHas(
                            'branch',
                            fn (Builder $branches): Builder => $branches
                                ->where('name', 'like', '%'.$term.'%')
                                ->orWhere('slug', 'like', '%'.$term.'%'),
                        );
                });
            }),
            AllowedFilter::callback('branch_id', function (Builder $query, mixed $value): void {
                if ($this->scopedBranchId !== null || $value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('branch_id', (int) $value);
            }),
            AllowedFilter::callback('product_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('product_id', (int) $value);
            }),
            AllowedFilter::callback('type', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('type', (string) $value);
            }),
            AllowedFilter::callback('from', function (Builder $query, mixed $value): void {
                $from = trim((string) $value);

                if ($from === '') {
                    return;
                }

                $query->whereDate('created_at', '>=', $from);
            }),
            AllowedFilter::callback('to', function (Builder $query, mixed $value): void {
                $to = trim((string) $value);

                if ($to === '') {
                    return;
                }

                $query->whereDate('created_at', '<=', $to);
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'branches' => $this->scopedBranchId !== null
                ? []
                : Branch::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'slug'])
                    ->map(fn (Branch $branch): array => [
                        'id' => $branch->id,
                        'name' => $branch->name,
                        'slug' => $branch->slug,
                    ])
                    ->all(),
            'products' => Product::query()
                ->orderBy('name')
                ->get(['id', 'name', 'sku'])
                ->map(fn (Product $product): array => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'sku' => $product->sku,
                ])
                ->all(),
            'types' => collect(InventoryTransactionType::cases())
                ->filter(fn (InventoryTransactionType $type): bool => in_array($type, [
                    InventoryTransactionType::StockIn,
                    InventoryTransactionType::StockOut,
                    InventoryTransactionType::Adjustment,
                ], true))
                ->map(fn (InventoryTransactionType $type): array => [
                    'value' => $type->value,
                    'label' => match ($type) {
                        InventoryTransactionType::StockIn => 'New entry',
                        InventoryTransactionType::StockOut => 'Reduce',
                        InventoryTransactionType::Adjustment => 'Adjustment',
                        default => $type->value,
                    },
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * @param  InventoryTransaction  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var InventoryTransaction $model */
        return [
            'id' => $model->id,
            'branch_id' => $model->branch_id,
            'branch_name' => $model->branch?->name,
            'product_id' => $model->product_id,
            'product_name' => $model->product?->name,
            'product_sku' => $model->product?->sku,
            'type' => $model->type->value,
            'type_label' => match ($model->type) {
                InventoryTransactionType::StockIn => 'New entry',
                InventoryTransactionType::StockOut => 'Reduce',
                InventoryTransactionType::Adjustment => 'Adjustment',
                InventoryTransactionType::Reserve => 'Reserve',
                InventoryTransactionType::Release => 'Release',
                InventoryTransactionType::Sale => 'Sale',
            },
            'quantity' => $model->quantity,
            'before_quantity' => $model->before_quantity,
            'after_quantity' => $model->after_quantity,
            'created_by_name' => $model->creator?->name,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
