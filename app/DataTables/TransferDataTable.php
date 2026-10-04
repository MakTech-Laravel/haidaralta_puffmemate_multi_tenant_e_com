<?php

namespace App\DataTables;

use App\Models\Branch;
use App\Models\UserTransferHistory;
use App\Support\WorkforcePayload;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<UserTransferHistory>
 */
class TransferDataTable extends AbstractDataTable
{
    /**
     * @return class-string<UserTransferHistory>
     */
    protected function modelClass(): string
    {
        return UserTransferHistory::class;
    }

    /**
     * @return Builder<UserTransferHistory>
     */
    protected function baseQuery(): Builder
    {
        return UserTransferHistory::query()
            ->with(['user:id,name,user_type', 'user.media', 'fromBranch:id,name,slug', 'toBranch:id,name,slug', 'transferredBy:id,name']);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            AllowedFilter::exact('user_id'),
            AllowedFilter::exact('subject_type'),
            AllowedFilter::exact('transfer_type'),
            AllowedFilter::exact('from_branch_id'),
            AllowedFilter::exact('to_branch_id'),
            AllowedFilter::callback('branch_id', function (Builder $query, mixed $value): void {
                if ($value === null || $value === '' || $value === 'all') {
                    return;
                }

                $branchId = (int) $value;

                $query->where(function (Builder $scope) use ($branchId): void {
                    $scope->where('from_branch_id', $branchId)
                        ->orWhere('to_branch_id', $branchId);
                });
            }),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return ['effective_date', 'created_at', 'id'];
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
            'branches' => Branch::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug'])
                ->map(fn (Branch $branch): array => [
                    'id' => $branch->id,
                    'name' => $branch->name,
                    'slug' => $branch->slug,
                ])
                ->all(),
            'subject_types' => WorkforcePayload::subjectTypeOptions(),
            'transfer_types' => WorkforcePayload::transferTypeOptions(),
        ];
    }

    /**
     * @param  UserTransferHistory  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var UserTransferHistory $model */
        return WorkforcePayload::transfer($model);
    }
}
