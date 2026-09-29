<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Branch;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<Pack>
 */
class BranchPackDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected Branch $branch,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch))->toArray();
    }

    /**
     * @return class-string<Pack>
     */
    protected function modelClass(): string
    {
        return Pack::class;
    }

    /**
     * @return Builder<Pack>
     */
    protected function baseQuery(): Builder
    {
        return Pack::query()
            ->where('branch_id', $this->branch->id)
            ->withCount('items');
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'slug'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['name', 'quantity', 'status', 'created_at', 'pack_price'];
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            StatusBooleanFilter::make('status'),
            $this->searchFilter(),
        ];
    }

    /**
     * @param  Pack  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Pack $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'image_url' => $model->imageUrl(),
            'product_count' => (int) ($model->items_count ?? 0),
            'subtotal' => $model->subtotal,
            'pack_price' => $model->pack_price,
            'quantity' => $model->quantity,
            'status' => (bool) $model->status,
            'starts_at' => $model->starts_at?->toDateString(),
            'ends_at' => $model->ends_at?->toDateString(),
            'is_highlighted' => $model->highlighted_at !== null,
        ];
    }
}
