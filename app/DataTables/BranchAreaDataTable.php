<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Models\Branch;
use App\Models\BranchArea;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\QueryBuilder\AllowedFilter;

/**
 * @extends AbstractDataTable<BranchArea>
 */
class BranchAreaDataTable extends AbstractDataTable
{
    public function __construct(
        Request $request,
        protected ?int $scopedBranchId = null,
        protected bool $includeBranch = false,
    ) {
        parent::__construct($request);
    }

    /**
     * @return array<string, mixed>
     */
    public static function forBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch->id, false))->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function acrossBranches(Request $request): array
    {
        return (new static($request, null, true))->toArray();
    }

    /**
     * @return class-string<BranchArea>
     */
    protected function modelClass(): string
    {
        return BranchArea::class;
    }

    /**
     * @return Builder<BranchArea>
     */
    protected function baseQuery(): Builder
    {
        return BranchArea::query()
            ->when($this->scopedBranchId !== null, function (Builder $query): void {
                $query->where('branch_id', $this->scopedBranchId);
            })
            ->when($this->includeBranch, fn (Builder $query) => $query->with('branch:id,name,slug'));
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        $filters = [
            StatusBooleanFilter::make('is_active'),
            $this->searchFilter(),
        ];

        if ($this->scopedBranchId === null) {
            $filters[] = AllowedFilter::exact('branch_id');
        }

        return $filters;
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name', 'address', 'city', 'state', 'postal_code'];
    }

    /**
     * @return list<string>
     */
    protected function allowedSorts(): array
    {
        return ['name', 'city', 'state', 'is_active', 'created_at'];
    }

    protected function defaultSort(): string
    {
        return 'name';
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        $options = [
            'is_active' => [
                ['value' => 'all', 'label' => 'All statuses'],
                ['value' => 'active', 'label' => 'Active'],
                ['value' => 'inactive', 'label' => 'Inactive'],
            ],
        ];

        if ($this->scopedBranchId === null) {
            $filters = $this->normalizedFilters();
            $branchId = $this->optionId($filters['branch_id'] ?? null);
            $options['selected_branch'] = $this->selectedBranch($branchId);
        }

        return $options;
    }

    private function optionId(mixed $value): ?int
    {
        if ($value === null || $value === '' || $value === 'all') {
            return null;
        }

        $id = (int) $value;

        return $id > 0 ? $id : null;
    }

    /**
     * @return array{id: int, name: string, slug: string}|null
     */
    private function selectedBranch(?int $branchId): ?array
    {
        if ($branchId === null) {
            return null;
        }

        $branch = Branch::query()->find($branchId, ['id', 'name', 'slug']);

        if ($branch === null) {
            return null;
        }

        return [
            'id' => $branch->id,
            'name' => $branch->name,
            'slug' => $branch->slug,
        ];
    }

    /**
     * @param  BranchArea  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var BranchArea $model */
        $row = [
            'id' => $model->id,
            'name' => $model->name,
            'address' => $model->address,
            'city' => $model->city,
            'state' => $model->state,
            'postal_code' => $model->postal_code,
            'is_active' => $model->is_active,
            'created_at' => $model->created_at?->toIso8601String(),
        ];

        if ($this->includeBranch) {
            $row['branch'] = $model->branch?->only(['id', 'name', 'slug']);
        }

        return $row;
    }
}
