<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreBranchRequest extends FormRequest
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
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('branches', 'slug')],
            'icon' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'is_active' => ['sometimes', 'boolean'],
            'super_admin_name' => ['required', 'string', 'max:255'],
            'super_admin_email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')],
            'super_admin_password' => ['required', 'confirmed', Password::defaults()],
        ];
    }
}
