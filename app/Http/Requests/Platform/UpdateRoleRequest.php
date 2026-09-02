<?php

namespace App\Http\Requests\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Support\PlatformTeamResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Spatie\Permission\Models\Role;

class UpdateRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Role $role */
        $role = $this->route('role');
        $teamKey = config('permission.column_names.team_foreign_key');
        $platformPermissions = array_map(
            fn (PermissionEnum $permission): string => $permission->value,
            PermissionEnum::forGuard('platform'),
        );

        $nameRules = [
            'required',
            'string',
            'max:255',
            Rule::unique('roles', 'name')
                ->where('guard_name', 'platform')
                ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID)
                ->ignore($role->id),
        ];

        if ($role->name === RoleEnum::SUPER_ADMIN->value) {
            $nameRules[] = Rule::in([RoleEnum::SUPER_ADMIN->value]);
        } else {
            $nameRules[] = Rule::notIn([RoleEnum::SUPER_ADMIN->value]);
        }

        return [
            'name' => $nameRules,
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($platformPermissions)],
        ];
    }
}
