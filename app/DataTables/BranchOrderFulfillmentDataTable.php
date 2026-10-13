<?php

namespace App\DataTables;

use App\DataTables\Concerns\FiltersOrderLists;
use App\Enums\FulfillmentStatus;
use App\Enums\OrderSource;
use App\Models\Branch;
use App\Models\Order;
use App\Models\OrderFulfillment;
use App\Services\Order\OrderAssignService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * Branch-scoped fulfillment list. Branch staff never see another branch's
 * rows — the query is always constrained by branch_id.
 *
 * @extends AbstractDataTable<OrderFulfillment>
 */
class BranchOrderFulfillmentDataTable extends AbstractDataTable
{
    use FiltersOrderLists;

    public function __construct(
        Request $request,
        protected int $branchId,
        protected OrderAssignService $orderAssign,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static(
            $request,
            $branch->id,
            app(OrderAssignService::class),
        ))->toArray();
    }

    /**
     * @return class-string<OrderFulfillment>
     */
    protected function modelClass(): string
    {
        return OrderFulfillment::class;
    }

    /**
     * @return Builder<OrderFulfillment>
     */
    protected function baseQuery(): Builder
    {
        return OrderFulfillment::query()
            ->where('branch_id', $this->branchId)
            ->with([
                'order:id,order_number,customer_id,payment_status,source,created_at',
                'order.customer:id,name,phone',
                'items',
                'driver:id,name',
            ]);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::callback('source', function (Builder $query, mixed $value): void {
                $source = OrderSource::tryFrom((string) $value);

                if ($source === null) {
                    return;
                }

                $query->whereHas('order', fn (Builder $order) => $order->where('source', $source->value));
            }),
            $this->orderSearchFilter(includeBranch: false, fulfillment: true),
            ...$this->placedDateFilters(throughOrder: true),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['fulfillment_number'];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'fulfillment_number',
            'status',
            'shipping_cost',
            'created_at',
            AllowedSort::callback('placed_at', function (Builder $query, bool $descending): void {
                $query->orderBy(
                    Order::query()
                        ->select('created_at')
                        ->whereColumn('orders.id', 'order_fulfillments.order_id')
                        ->limit(1),
                    $descending ? 'desc' : 'asc',
                );
            }),
        ];
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
            'status' => FulfillmentStatus::options(),
            'source' => OrderSource::options(),
        ];
    }

    /**
     * @param  OrderFulfillment  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        return [
            'id' => $model->id,
            'fulfillment_number' => $model->fulfillment_number,
            'order_id' => $model->order_id,
            'order_number' => $model->order->order_number,
            'customer_name' => $model->order->customer?->name ?? 'Customer',
            'customer_phone' => $model->order->customer?->phone,
            'placed_at' => $model->order->created_at?->toIso8601String(),
            'source' => $model->order->source->value,
            'source_label' => $model->order->source->label(),
            'items_count' => $model->items->count(),
            'status' => $model->status->value,
            'shipping_cost' => (float) $model->shipping_cost,
            'is_paid' => $model->isPaid(),
            'order_payment_status' => $model->order->payment_status->value,
            'created_at' => $model->created_at?->toIso8601String(),
            'can_assign' => $this->orderAssign->canAssign($model),
            ...$this->orderAssign->assignStatusPayload($model),
        ];
    }
}
