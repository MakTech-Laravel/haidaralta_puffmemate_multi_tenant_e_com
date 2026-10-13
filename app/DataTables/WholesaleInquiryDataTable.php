<?php

namespace App\DataTables;

use App\Enums\WholesaleInquiryStatus;
use App\Models\WholesaleInquiry;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<WholesaleInquiry>
 */
class WholesaleInquiryDataTable extends AbstractDataTable
{
    /**
     * @return class-string<WholesaleInquiry>
     */
    protected function modelClass(): string
    {
        return WholesaleInquiry::class;
    }

    /**
     * @return Builder<WholesaleInquiry>
     */
    protected function baseQuery(): Builder
    {
        return WholesaleInquiry::query()
            ->withCount('items')
            ->with('order:id,order_number');
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['full_name', 'company', 'email', 'phone'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['full_name', 'status', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return '-created_at';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        return [
            'status' => WholesaleInquiryStatus::options(),
        ];
    }

    /**
     * @param  WholesaleInquiry  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var WholesaleInquiry $model */
        return [
            'id' => $model->id,
            'full_name' => $model->full_name,
            'phone' => $model->phone,
            'email' => $model->email,
            'company' => $model->company,
            'status' => $model->status->value,
            'status_label' => $model->status->label(),
            'is_open' => $model->status->isOpen(),
            'items_count' => (int) ($model->items_count ?? 0),
            'order_id' => $model->order?->id,
            'order_number' => $model->order?->order_number,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
