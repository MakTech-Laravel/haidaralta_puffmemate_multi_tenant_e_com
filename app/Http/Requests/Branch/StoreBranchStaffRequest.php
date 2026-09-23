<?php

namespace App\Http\Requests\Branch;

use App\Concerns\NormalizesPhoneInput;
use App\Enums\RoleEnum;
use App\Models\Branch;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\Validator;

class StoreBranchStaffRequest extends FormRequest
{
    use NormalizesPhoneInput;

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedPhone();
        $this->normalizeBlankEmailToNull();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var Branch $branch */
        $branch = app('currentBranch');
        $teamKey = config('permission.column_names.team_foreign_key');

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule(), Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'roles' => ['required', 'array', 'min:1', 'max:1'],
            'roles.*' => [
                'string',
                Rule::exists('roles', 'name')
                    ->where('guard_name', 'branch')
                    ->where($teamKey, $branch->id),
            ],
            'is_active' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $roles = $this->input('roles', []);

            if (! in_array(RoleEnum::SUPER_ADMIN->value, $roles, true)) {
                return;
            }

            if (! $this->user('branch')?->hasRole(RoleEnum::SUPER_ADMIN->value)) {
                $validator->errors()->add('roles', 'Only a super-admin can assign the super-admin role.');
            }
        });
    }
}
