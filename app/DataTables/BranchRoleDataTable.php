<?php

namespace App\DataTables;

use App\DataTables\Concerns\MapsRoleRows;
use App\DataTables\Filters\RoleTableFilters;
use App\Models\Branch;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;
use Spatie\QueryBuilder\AllowedSort;

/**
 * @extends AbstractDataTable<Role>
 */
class BranchRoleDataTable extends AbstractDataTable
{
    use MapsRoleRows;

    public function __construct(
        Request $request,
        protected Branch $branch,
    ) {
        parent::__construct($request);

        app(PermissionRegistrar::class)->setPermissionsTeamId($branch->id);
    }

    /**
     * @return array<string, mixed>
     */
    public static function makeForBranch(Request $request, Branch $branch): array
    {
        return (new static($request, $branch))->toArray();
    }

    /**
     * @return class-string<Role>
     */
    protected function modelClass(): string
    {
        return Role::class;
    }

    /**
     * @return Builder<Role>
     */
    protected function baseQuery(): Builder
    {
        $teamKey = config('permission.column_names.team_foreign_key');

        return Role::query()
            ->where('guard_name', 'branch')
            ->where($teamKey, $this->branch->id)
            ->withCount(['permissions', 'users']);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            RoleTableFilters::kind('branch'),
            RoleTableFilters::users(),
            RoleTableFilters::permissions('branch'),
            $this->searchFilter(),
        ];
    }

    /**
     * @return list<string>
     */
    protected function modelSearchableColumns(): array
    {
        return ['name'];
    }

    /**
     * @return list<AllowedSort|string>
     */
    protected function allowedSorts(): array
    {
        return [
            'name',
            'permissions_count',
            'users_count',
        ];
    }

    /**
     * @param  Role  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        return $this->mapRoleRow($model, 'branch');
    }
}
