<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;

class BranchStaffDataTable extends AbstractDataTable
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
    public static function forBranch(Request $request, Branch $branch, bool $includeBranch = false): array
    {
        return (new static($request, $branch->id, $includeBranch))->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public static function acrossBranches(Request $request): array
    {
        return (new static($request, null, true))->toArray();
    }

    /**
     * @return class-string<User>
     */
    protected function modelClass(): string
    {
        return User::class;
    }

    /**
     * @return Builder<User>
     */
    protected function baseQuery(): Builder
    {
        return User::query()
            ->where('user_type', UserType::BRANCH)
            ->when($this->scopedBranchId !== null, fn (Builder $query) => $query->where('branch_id', $this->scopedBranchId))
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
        return ['name', 'phone', 'email'];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'phone',
            'email',
            'is_active',
            'created_at',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function filterOptions(): array
    {
        if ($this->scopedBranchId !== null) {
            return [];
        }

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
        ];
    }

    /**
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var User $model */
        $roles = [];

        if ($model->branch_id !== null) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($model->branch_id);
            $roles = $model->getRoleNames()->values()->all();
        }

        $row = [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'email' => $model->email,
            'is_active' => (bool) $model->is_active,
            'roles' => $roles,
        ];

        if ($this->includeBranch) {
            $row['branch'] = $model->branch?->only(['id', 'name', 'slug']);
        }

        return $row;
    }
}
