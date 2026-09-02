<?php

namespace App\Http\Requests\Platform;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Support\PlatformTeamResolver;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
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
        $teamKey = config('permission.column_names.team_foreign_key');
        $platformPermissions = array_map(
            fn (PermissionEnum $permission): string => $permission->value,
            PermissionEnum::forGuard('platform'),
        );

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::notIn([RoleEnum::SUPER_ADMIN->value]),
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'platform')
                    ->where($teamKey, PlatformTeamResolver::PLATFORM_TEAM_ID),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($platformPermissions)],
        ];
    }
}
