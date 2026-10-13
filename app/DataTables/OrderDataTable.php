<?php

namespace App\DataTables;

use App\DataTables\Concerns\FiltersOrderLists;
use App\Enums\OrderSource;
use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Services\Order\OrderAssignService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Platform-wide master order list — every order across every branch.
 *
 * @extends AbstractDataTable<Order>
 */
class OrderDataTable extends AbstractDataTable
{
    use FiltersOrderLists;

    public function __construct(
        Request $request,
        protected OrderAssignService $orderAssign,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function make(Request $request): array
    {
        return (new static($request, app(OrderAssignService::class)))->toArray();
    }

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
        return Order::query()->with([
            'customer:id,name,phone',
            'placedBy:id,name',
            'fulfillment.branch:id,name,slug',
            'fulfillment.driver:id,name',
        ]);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::exact('payment_status'),
            AllowedFilter::exact('source'),
            $this->orderSearchFilter(includeBranch: true),
            ...$this->placedDateFilters(throughOrder: false),
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
            'source' => OrderSource::options(),
        ];
    }

    /**
     * @param  Order  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        $fulfillment = $model->fulfillment;

        $assign = $fulfillment === null
            ? [
                'fulfillment_id' => null,
                'fulfillment_number' => null,
                'can_assign' => false,
                'assign_status' => 'not_assign',
                'driver_id' => null,
                'driver_name' => null,
            ]
            : [
                'fulfillment_id' => $fulfillment->id,
                'fulfillment_number' => $fulfillment->fulfillment_number,
                'can_assign' => $this->orderAssign->canAssign($fulfillment),
                ...$this->orderAssign->assignStatusPayload($fulfillment),
            ];

        return [
            'id' => $model->id,
            'order_number' => $model->order_number,
            'customer_id' => $model->customer_id,
            'customer_name' => $model->customer?->name ?? 'Customer',
            'customer_phone' => $model->customer?->phone,
            'fulfillment_branch' => $fulfillment?->branch?->name ?? '—',
            'branch_slug' => $fulfillment?->branch?->slug,
            'total_label' => '$'.number_format((float) $model->total_amount, 2),
            'status' => $model->status->value,
            'status_label' => $model->status->label(),
            'payment_status' => $model->payment_status->value,
            'payment_status_label' => $model->payment_status->label(),
            'source' => $model->source->value,
            'source_label' => $model->source->label(),
            'created_at' => $model->created_at?->toIso8601String(),
            ...$assign,
        ];
    }
}
