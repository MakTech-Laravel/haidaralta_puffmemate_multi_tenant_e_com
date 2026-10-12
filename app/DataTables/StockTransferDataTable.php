<?php

namespace App\DataTables;

use App\Enums\StockTransferStatus;
use App\Models\Branch;
use App\Models\StockTransfer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * Inter-branch stock movements created for an order.
 *
 * @extends AbstractDataTable<StockTransfer>
 */
class StockTransferDataTable extends AbstractDataTable
{
    public function __construct(Request $request, protected ?int $branchId = null)
    {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch->id))->toArray();
    }

    /**
     * @return class-string<StockTransfer>
     */
    protected function modelClass(): string
    {
        return StockTransfer::class;
    }

    /**
     * @return Builder<StockTransfer>
     */
    protected function baseQuery(): Builder
    {
        $query = StockTransfer::query()->with([
            'items.product:id,name',
            'sourceBranch:id,name',
            'destinationBranch:id,name',
            'order:id,order_number',
        ]);

        if ($this->branchId === null) {
            return $query;
        }

        return $query->where(function (Builder $scope): void {
            $scope->where('source_branch_id', $this->branchId)
                ->orWhere('destination_branch_id', $this->branchId);
        });
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('status'),
            AllowedFilter::callback('search', function (Builder $query, mixed $value): void {
                $term = trim((string) $value);

                if ($term === '') {
                    return;
                }

                $like = '%'.$term.'%';

                $query->where(function (Builder $rows) use ($like): void {
                    $rows->whereHas('order', function (Builder $order) use ($like): void {
                        $order->where('order_number', 'like', $like);
                    })->orWhereHas('sourceBranch', function (Builder $branch) use ($like): void {
                        $branch->where('name', 'like', $like);
                    })->orWhereHas('destinationBranch', function (Builder $branch) use ($like): void {
                        $branch->where('name', 'like', $like);
                    })->orWhereHas('items.product', function (Builder $product) use ($like): void {
                        $product->where('name', 'like', $like);
                    });
                });
            }),
        ];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['created_at', 'estimated_cost', 'status', 'id'];
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
            'status' => StockTransferStatus::options(),
        ];
    }

    /**
     * @param  StockTransfer  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        $isPlatform = $this->branchId === null;
        $isSource = $isPlatform || $model->source_branch_id === $this->branchId;
        $isDestination = $isPlatform || $model->destination_branch_id === $this->branchId;

        return [
            'id' => $model->id,
            'order_number' => $model->order?->order_number,
            'status' => $model->status->value,
            'status_label' => $model->status->label(),
            'source_name' => $model->sourceBranch?->name ?? '—',
            'destination_name' => $model->destinationBranch?->name ?? '—',
            'estimated_cost_label' => '$'.number_format((float) $model->estimated_cost, 2),
            'items_label' => $model->items
                ->map(fn ($item): string => ($item->product?->name ?? 'Product').' × '.$item->quantity)
                ->join(', '),
            'created_at' => $model->created_at?->toIso8601String(),
            'can_approve' => $isSource && $model->status->sourceAction() === 'approve',
            'can_pick' => $isSource && $model->status->sourceAction() === 'pick',
            'can_dispatch' => $isSource && $model->status->sourceAction() === 'dispatch',
            'can_receive' => $isDestination && $model->status->destinationAction() === 'receive',
        ];
    }
}
