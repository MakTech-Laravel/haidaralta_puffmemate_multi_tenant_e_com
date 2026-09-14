<?php

namespace App\Http\Requests\Platform;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class StoreBranchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('phone') === '' || $this->input('phone') === null) {
            $this->merge(['phone' => null]);
        } else {
            $raw = $this->input('phone');

            if (is_string($raw)) {
                try {
                    $this->merge(['phone' => Phone::toE164($raw)]);
                } catch (Throwable) {
                    // Leave raw value for the phone validation rule.
                }
            }
        }

        $superAdmin = $this->input('super_admin');

        if (! is_array($superAdmin)) {
            return;
        }

        if (($superAdmin['email'] ?? null) === '') {
            $superAdmin['email'] = null;
        }

        $adminPhone = $superAdmin['phone'] ?? null;

        if (is_string($adminPhone) && $adminPhone !== '') {
            try {
                $superAdmin['phone'] = Phone::toE164($adminPhone);
            } catch (Throwable) {
                // Leave raw value for the phone validation rule.
            }
        }

        $this->merge(['super_admin' => $superAdmin]);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('branches', 'slug')],
            'code' => ['required', 'string', 'max:50', Rule::unique('branches', 'code')],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'phone' => ['nullable', 'string', ...Phone::validationRule(mobileOnly: false)],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
            'status' => ['sometimes', 'boolean'],
            'super_admin' => ['required', 'array'],
            'super_admin.name' => ['required', 'string', 'max:255'],
            'super_admin.phone' => ['required', 'string', ...Phone::validationRule(), Rule::unique('users', 'phone')],
            'super_admin.email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'super_admin.password' => ['required', 'confirmed', Password::defaults()],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'super_admin.name' => 'super-admin name',
            'super_admin.phone' => 'super-admin phone',
            'super_admin.email' => 'super-admin email',
            'super_admin.password' => 'super-admin password',
        ];
    }
}
