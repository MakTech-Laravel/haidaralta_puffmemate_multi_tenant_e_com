<?php

namespace App\Http\Requests\Platform;

use App\Models\PaymentGatewaySetting;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdatePaymentGatewaySettingsRequest extends FormRequest
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
            'methods' => ['required', 'array', 'size:3'],
            'methods.*.key' => ['required', 'string', Rule::in(PaymentGatewaySetting::KEYS)],
            'methods.*.label' => ['required', 'string', 'max:120'],
            'methods.*.description' => ['nullable', 'string', 'max:255'],
            'methods.*.enabled' => ['required', 'boolean'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $methods = $this->input('methods', []);

            if (! is_array($methods)) {
                return;
            }

            $enabledCount = collect($methods)->where('enabled', true)->count();

            if ($enabledCount < 1) {
                $validator->errors()->add('methods', 'At least one payment gateway must remain enabled.');
            }

            $keys = collect($methods)->pluck('key')->filter()->values();

            if ($keys->unique()->count() !== $keys->count()) {
                $validator->errors()->add('methods', 'Each payment gateway key must be unique.');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $methods = $this->input('methods', []);

        if (! is_array($methods)) {
            return;
        }

        $normalized = [];

        foreach ($methods as $method) {
            if (! is_array($method)) {
                continue;
            }

            $normalized[] = [
                ...$method,
                'enabled' => filter_var($method['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN),
            ];
        }

        $this->merge(['methods' => $normalized]);
    }
}
