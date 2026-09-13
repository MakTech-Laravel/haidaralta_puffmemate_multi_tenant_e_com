<?php

namespace App\DataTables;

use App\DataTables\Filters\StatusBooleanFilter;
use App\Enums\UserType;
use App\Models\User;
use App\Support\PlatformTeamResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Spatie\QueryBuilder\AllowedFilter;

class PlatformStaffDataTable extends AbstractDataTable
{
    public function __construct(Request $request)
    {
        parent::__construct($request);

        app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
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
        return User::query()->where('user_type', UserType::PLATFORM);
    }

    /**
     * @return list<AllowedFilter|string>
     */
    protected function allowedFilters(): array
    {
        return [
            StatusBooleanFilter::make('is_active'),
            $this->searchFilter(),
        ];
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
     * @param  User  $model
     * @return array<string, mixed>
     */
    protected function mapRow(Model $model): array
    {
        /** @var User $model */
        return [
            'id' => $model->id,
            'name' => $model->name,
            'phone' => $model->phone,
            'email' => $model->email,
            'is_active' => (bool) $model->is_active,
            'roles' => $model->getRoleNames()->values()->all(),
        ];
    }
}
