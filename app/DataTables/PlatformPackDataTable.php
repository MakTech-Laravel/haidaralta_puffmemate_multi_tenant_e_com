<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Branch;
use App\Models\Pack;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Every branch's packs for the platform Packs & Saves list.
 *
 * @extends AbstractDataTable<Pack>
 */
class PlatformPackDataTable extends AbstractDataTable
{
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
            ->with(['branch:id,name,slug', 'media'])
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
            AllowedFilter::exact('branch_id'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $branchId = (int) ($this->normalizedFilters()['branch_id'] ?? 0);
        $branch = $branchId > 0 ? Branch::query()->find($branchId, ['id', 'name', 'slug']) : null;

        return [
            'selected_branch' => $branch === null ? null : [
                'id' => $branch->id,
                'name' => $branch->name,
                'slug' => $branch->slug,
            ],
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
            'branch_name' => $model->branch->name ?? '',
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
