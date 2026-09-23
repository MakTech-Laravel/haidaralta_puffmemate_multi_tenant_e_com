<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\Branch;
use App\Models\User;
use App\Support\MediaUrl;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<User>
 */
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
            ->when($this->scopedBranchId !== null, function (Builder $query): void {
                $query->where('branch_id', $this->scopedBranchId)->with('roles');
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

        if ($this->scopedBranchId !== null) {
            $filters[] = $this->roleFilter();
        }

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
            return [
                'is_active' => [
                    ['value' => 'all', 'label' => 'All statuses'],
                    ['value' => 'active', 'label' => 'Active'],
                    ['value' => 'inactive', 'label' => 'Inactive'],
                ],
                'roles' => collect(RoleEnum::cases())
                    ->map(fn (RoleEnum $role): array => [
                        'value' => $role->value,
                        'label' => $this->roleLabel($role),
                    ])
                    ->all(),
            ];
        }

        $filters = $this->normalizedFilters();
        $branchId = $this->optionId($filters['branch_id'] ?? null);

        return [
            'selected_branch' => $this->selectedBranch($branchId),
        ];
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

    protected function roleFilter(): AllowedFilter
    {
        return AllowedFilter::callback('role', function (Builder $query, mixed $value): void {
            $role = RoleEnum::tryFrom(is_string($value) ? $value : '');

            if ($role === null) {
                return;
            }

            app(PermissionRegistrar::class)->setPermissionsTeamId($this->scopedBranchId);
            $query->role($role->value, 'branch');
        });
    }

    protected function roleLabel(RoleEnum $role): string
    {
        return match ($role) {
            RoleEnum::SUPER_ADMIN => 'Super admin',
            RoleEnum::ADMIN => 'Admin',
            RoleEnum::MANAGER => 'Manager',
            RoleEnum::STAFF => 'Staff',
        };
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
            'avatar_url' => MediaUrl::resolve($model->avatar, fallback: null),
            'is_active' => (bool) $model->is_active,
            'roles' => $roles,
        ];

        if ($this->includeBranch) {
            $row['branch'] = $model->branch?->only(['id', 'name', 'slug']);
        }

        return $row;
    }
}
