<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSmtpSettingsRequest extends FormRequest
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
        $enabled = $this->boolean('enabled');

        return [
            'enabled' => ['required', 'boolean'],
            'host' => [Rule::requiredIf($enabled), 'nullable', 'string', 'max:255'],
            'port' => [Rule::requiredIf($enabled), 'nullable', 'integer', 'min:1', 'max:65535'],
            'encryption' => [Rule::requiredIf($enabled), 'nullable', Rule::in(['tls', 'ssl', 'none'])],
            'username' => ['nullable', 'string', 'max:255'],
            'password' => ['nullable', 'string', 'max:255'],
            'from_address' => [Rule::requiredIf($enabled), 'nullable', 'email', 'max:255'],
            'from_name' => ['nullable', 'string', 'max:120'],
            'reply_to_address' => ['nullable', 'email', 'max:255'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'enabled' => $this->boolean('enabled'),
        ]);
    }
}
