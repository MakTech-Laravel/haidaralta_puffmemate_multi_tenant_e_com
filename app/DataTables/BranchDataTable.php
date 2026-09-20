<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Branch;
use App\Services\Branch\BranchService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<Branch>
 */
class BranchDataTable extends AbstractDataTable
{
    protected BranchService $branchService;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        $this->branchService = app(BranchService::class);
    }

    /**
     * @return class-string<Branch>
     */
    protected function modelClass(): string
    {
        return Branch::class;
    }

    /**
     * @return Builder<Branch>
     */
    protected function baseQuery(): Builder
    {
        return Branch::query()->withCount('users');
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
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'slug',
            'code',
            'status',
            'created_at',
            'users_count',
        ];
    }

    /**
     * @param  Branch  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var Branch $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'slug' => $model->slug,
            'code' => $model->code,
            'logo' => $model->logo,
            'logo_url' => $this->branchService->logoUrl($model->logo),
            'status' => (bool) $model->status,
            'users_count' => (int) ($model->users_count ?? 0),
            'created_at' => $model->created_at?->toIso8601String(),
        ];
    }
}
