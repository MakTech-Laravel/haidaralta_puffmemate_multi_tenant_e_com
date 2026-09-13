<?php

namespace App\DataTables\Concerns;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Role;
use Illuminate\Database\Eloquent\Model;

trait MapsRoleRows
{
    /**
     * @param  Role  $model
     * @return array<string, mixed>
     */
    protected function mapRoleRow(Model $model, string $guard): array
    {
        /** @var Role $model */
        $isSuperAdmin = $model->name === RoleEnum::SUPER_ADMIN->value;

        return [
            'id' => $model->id,
            'name' => $model->name,
            'permissions_count' => $isSuperAdmin
                ? count(PermissionEnum::forGuard($guard))
                : (int) ($model->permissions_count ?? 0),
            'users_count' => (int) ($model->users_count ?? 0),
            'is_super_admin' => $isSuperAdmin,
        ];
    }
}
