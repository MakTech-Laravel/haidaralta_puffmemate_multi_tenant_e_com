<?php

namespace App\DataTables;

use App\Models\OrderDriverAssignmentHistory;
use App\Models\OrderFulfillment;
use App\Models\User;
use App\Support\Order\AssignmentHistoryActor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<OrderDriverAssignmentHistory>
 */
class BranchOrderAssignHistoryDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected int $branchId,
        protected string $branchSlug,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, int $branchId, string $branchSlug): array
    {
        return static::instanceFor($request, $branchId, $branchSlug)->toArray();
    }

    public static function instanceFor(Request $request, int $branchId, string $branchSlug): static
    {
        return new static($request, $branchId, $branchSlug);
    }

    /**
     * @return class-string<OrderDriverAssignmentHistory>
     */
    protected function modelClass(): string
    {
        return OrderDriverAssignmentHistory::class;
    }

    /**
     * @return Builder<OrderDriverAssignmentHistory>
     */
    protected function baseQuery(): Builder
    {
        return OrderDriverAssignmentHistory::query()
            ->whereHas(
                'fulfillment',
                fn (Builder $fulfillments): Builder => $fulfillments->where('branch_id', $this->branchId),
            )
            ->with([
                'fulfillment:id,fulfillment_number,branch_id',
                'fromDriver:id,name,phone',
                'toDriver:id,name,phone',
                'changer:id,name,user_type,branch_id',
            ]);
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['created_at', 'id'];
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
                    $builder->whereHas(
                        'fulfillment',
                        fn (Builder $fulfillments): Builder => $fulfillments
                            ->where('fulfillment_number', 'like', '%'.$term.'%'),
                    )->orWhereHas(
                        'fromDriver',
                        fn (Builder $drivers): Builder => $drivers
                            ->where('name', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%'),
                    )->orWhereHas(
                        'toDriver',
                        fn (Builder $drivers): Builder => $drivers
                            ->where('name', 'like', '%'.$term.'%')
                            ->orWhere('phone', 'like', '%'.$term.'%'),
                    );
                });
            }),
            AllowedFilter::callback('fulfillment_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $query->where('fulfillment_id', (int) $value);
            }),
            AllowedFilter::callback('order_id', function (Builder $query, mixed $value): void {
                $term = trim((string) $value);

                if ($term === '') {
                    return;
                }

                $query->whereHas(
                    'fulfillment',
                    fn (Builder $fulfillments): Builder => $fulfillments
                        ->where('fulfillment_number', 'like', '%'.$term.'%'),
                );
            }),
            AllowedFilter::callback('driver_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $driverId = (int) $value;

                $query->where(function (Builder $builder) use ($driverId): void {
                    $builder->where('from_driver_id', $driverId)
                        ->orWhere('to_driver_id', $driverId);
                });
            }),
            AllowedFilter::callback('date', function (Builder $query, mixed $value): void {
                $date = trim((string) $value);

                if ($date === '') {
                    return;
                }

                $query->whereDate('created_at', $date);
            }),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $filters = $this->normalizedFilters();
        $driverId = isset($filters['driver_id']) ? (int) $filters['driver_id'] : 0;
        $fulfillmentId = isset($filters['fulfillment_id']) ? (int) $filters['fulfillment_id'] : 0;

        $selectedDriver = null;

        if ($driverId > 0) {
            $driver = User::query()->whereKey($driverId)->first(['id', 'name', 'phone']);

            if ($driver !== null) {
                $selectedDriver = [
                    'id' => $driver->id,
                    'name' => $driver->name,
                    'phone' => $driver->phone,
                ];
            }
        }

        $selectedFulfillment = null;

        if ($fulfillmentId > 0) {
            $fulfillment = OrderFulfillment::query()
                ->whereKey($fulfillmentId)
                ->where('branch_id', $this->branchId)
                ->first(['id', 'fulfillment_number']);

            if ($fulfillment !== null) {
                $selectedFulfillment = [
                    'id' => $fulfillment->id,
                    'fulfillment_number' => $fulfillment->fulfillment_number,
                ];
            }
        }

        return [
            'selected_driver' => $selectedDriver,
            'selected_fulfillment' => $selectedFulfillment,
        ];
    }

    /**
     * @param  OrderDriverAssignmentHistory  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        $fulfillment = $model->fulfillment;
        $changedBy = AssignmentHistoryActor::changedBy(
            $model->changer,
            $this->branchId,
            $this->branchSlug,
        );

        return [
            'id' => $model->id,
            'fulfillment_id' => $model->fulfillment_id,
            'fulfillment_number' => $fulfillment?->fulfillment_number,
            'fulfillment_url' => $fulfillment !== null
                ? route('branch.orders.show', [$this->branchSlug, $fulfillment->id])
                : null,
            'from_driver_id' => $model->from_driver_id,
            'from_driver_name' => $model->fromDriver?->name,
            'from_driver_phone' => $model->fromDriver?->phone,
            'from_driver_url' => $model->from_driver_id !== null
                ? route('branch.drivers.show', [$this->branchSlug, $model->from_driver_id])
                : null,
            'to_driver_id' => $model->to_driver_id,
            'to_driver_name' => $model->toDriver?->name,
            'to_driver_phone' => $model->toDriver?->phone,
            'to_driver_url' => $model->to_driver_id !== null
                ? route('branch.drivers.show', [$this->branchSlug, $model->to_driver_id])
                : null,
            'changed_by_name' => $changedBy['name'],
            'changed_by_url' => $changedBy['url'],
            'reason' => $model->reason,
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
