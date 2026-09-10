<?php

namespace App\Http\Requests\Platform;

use App\Enums\RoleEnum;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class UpdatePlatformBranchStaffRequest extends FormRequest
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
        /** @var User $user */
        $user = $this->route('user');
        $teamKey = config('permission.column_names.team_foreign_key');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user->id),
            ],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'branch')
                    ->where($teamKey, $user->branch_id),
            ],
            'is_active' => ['required', 'boolean'],
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
