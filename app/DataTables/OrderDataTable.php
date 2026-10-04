<?php

namespace App\DataTables;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Platform-wide master order list — every order across every branch.
 *
 * @extends AbstractDataTable<Order>
 */
class OrderDataTable extends AbstractDataTable
{
    /**
     * @return class-string<Order>
     */
    protected function modelClass(): string
    {
        return Order::class;
    }

    /**
     * @return Builder<Order>
     */
    protected function baseQuery(): Builder
    {
        return Order::query()->with(['customer:id,name', 'fulfillment.branch:id,name']);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('payment_status'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['order_number'];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return ['order_number', 'status', 'payment_status', 'total_amount', 'created_at'];
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
            'status' => OrderStatus::options(),
            'payment_status' => PaymentStatus::options(),
        ];
    }

    /**
     * @param  Order  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        return [
            'id' => $model->id,
            'order_number' => $model->order_number,
            'customer_name' => $model->customer?->name ?? 'Customer',
            'fulfillment_branch' => $model->fulfillment?->branch?->name ?? '—',
            'total_label' => '$'.number_format((float) $model->total_amount, 2),
            'status' => $model->status->value,
            'status_label' => $model->status->label(),
            'payment_status' => $model->payment_status->value,
            'payment_status_label' => $model->payment_status->label(),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
