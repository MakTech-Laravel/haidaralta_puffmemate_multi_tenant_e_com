<?php

namespace App\DataTables;

use App\Enums\FulfillmentStatus;
use App\Models\Branch;
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
                'order:id,order_number,customer_id,payment_status',
                'order.customer:id,name',
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
            $this->searchFilter(),
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
        return ['fulfillment_number', 'status', 'shipping_cost', 'created_at'];
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
