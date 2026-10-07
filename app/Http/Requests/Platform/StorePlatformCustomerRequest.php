<?php

namespace App\Http\Requests\Platform;

use App\Concerns\NormalizesPhoneInput;
use App\Enums\AustralianState;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Throwable;

class StorePlatformCustomerRequest extends FormRequest
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
        $this->normalizeAddressPayloads();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $billingPhoneRules = ['nullable', 'string'];

        if (filled($this->input('billing.phone'))) {
            $billingPhoneRules = ['nullable', 'string', ...Phone::validationRule()];
        }

        return [
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule(), Rule::unique('users', 'phone')],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('users', 'email')],
            'password' => ['required', 'confirmed', Password::defaults()],
            'is_active' => ['sometimes', 'boolean'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'shipping' => ['sometimes', 'array'],
            'shipping.address' => ['nullable', 'string', 'max:2000'],
            'shipping.city' => ['nullable', 'string', 'max:255'],
            'shipping.region' => ['nullable', Rule::enum(AustralianState::class)],
            'shipping.zip' => ['nullable', 'string', 'max:16'],
            'billing' => ['sometimes', 'array'],
            'billing.name' => ['nullable', 'string', 'max:255'],
            'billing.address' => ['nullable', 'string', 'max:2000'],
            'billing.city' => ['nullable', 'string', 'max:255'],
            'billing.region' => ['nullable', Rule::enum(AustralianState::class)],
            'billing.zip' => ['nullable', 'string', 'max:16'],
            'billing.email' => ['nullable', 'email', 'max:255'],
            'billing.phone' => $billingPhoneRules,
        ];
    }

    private function normalizeAddressPayloads(): void
    {
        $shipping = $this->input('shipping');

        if (is_array($shipping)) {
            foreach (['address', 'city', 'region', 'zip'] as $field) {
                if (($shipping[$field] ?? null) === '') {
                    $shipping[$field] = null;
                }
            }

            $this->merge(['shipping' => $shipping]);
        }

        $billing = $this->input('billing');

        if (! is_array($billing)) {
            return;
        }

        foreach (['name', 'address', 'city', 'region', 'zip', 'email', 'phone'] as $field) {
            if (($billing[$field] ?? null) === '') {
                $billing[$field] = null;
            }
        }

        if (is_string($billing['phone'] ?? null) && $billing['phone'] !== '') {
            try {
                $billing['phone'] = Phone::toE164($billing['phone']);
            } catch (Throwable) {
                // Leave raw value for the phone validation rule.
            }
        }

        $this->merge(['billing' => $billing]);
    }
}
