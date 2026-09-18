<?php

namespace App\Http\Requests\Branch;

use App\Enums\PermissionEnum;
use App\Enums\RoleEnum;
use App\Models\Branch;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('name') && is_string($this->input('name'))) {
            $this->merge([
                'name' => trim($this->input('name')),
            ]);
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');
        $teamKey = config('permission.column_names.team_foreign_key');
        $branchPermissions = array_map(
            fn (PermissionEnum $permission): string => $permission->value,
            PermissionEnum::forGuard('branch'),
        );

        return [
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::notIn([RoleEnum::SUPER_ADMIN->value]),
                Rule::unique('roles', 'name')
                    ->where('guard_name', 'branch')
                    ->where($teamKey, $branch->id),
            ],
            'permissions' => ['nullable', 'array'],
            'permissions.*' => ['string', Rule::in($branchPermissions)],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A role with this name already exists.',
        ];
    }
}
