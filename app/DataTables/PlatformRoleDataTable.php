<?php

namespace App\DataTables;

use App\DataTables\Concerns\MapsRoleRows;
use App\DataTables\Filters\RoleTableFilters;
use App\Models\Role;
use App\Support\PlatformTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;

class PlatformRoleDataTable extends AbstractDataTable
{
    use MapsRoleRows;

    public function __construct(Request $request)
    {
        parent::__construct($request);

        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
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
            ->where('guard_name', 'platform')
            ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID)
            ->withCount(['permissions', 'users']);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            RoleTableFilters::kind('platform'),
            RoleTableFilters::users(),
            RoleTableFilters::permissions('platform'),
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
        return $this->mapRoleRow($model, 'platform');
    }
}
