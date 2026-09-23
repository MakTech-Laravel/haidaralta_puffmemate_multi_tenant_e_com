<?php

namespace App\Http\Requests\Platform;

use App\Enums\RoleEnum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class TransferBranchStaffRequest extends FormRequest
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
        $branchId = $this->integer('to_branch_id');

        return [
            'to_branch_id' => ['required', 'integer', Rule::exists('branches', 'id')],
            'roles' => ['required', 'array', 'min:1', 'max:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'branch')
                    ->where($teamKey, $branchId),
            ],
            'effective_date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:500'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roles = $this->input('roles', []);

            if (! in_array(RoleEnum::SUPER_ADMIN->value, $roles, true)) {
                return;
            }

            if (! $this->user('platform')?->hasRole(RoleEnum::SUPER_ADMIN->value)) {
                $validator->errors()->add('roles', 'Only a platform super-admin can assign the branch super-admin role.');
            }
        });
    }
}
