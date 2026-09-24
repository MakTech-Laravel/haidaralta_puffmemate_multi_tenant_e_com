<?php

namespace App\Http\Requests\Branch;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class UpdateBranchSettingsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('delivery_radius_km') === '') {
            $this->merge(['delivery_radius_km' => null]);
        }

        if ($this->input('phone') === '' || $this->input('phone') === null) {
            $this->merge(['phone' => null]);

            return;
        }

        $raw = $this->input('phone');

        if (! is_string($raw)) {
            return;
        }

        try {
            $this->merge(['phone' => Phone::toE164($raw)]);
        } catch (Throwable) {
            // Leave raw value for the phone validation rule.
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'phone' => ['nullable', 'string', ...Phone::validationRule(mobileOnly: false)],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'delivery_radius_km' => ['nullable', 'numeric', 'min:0.1', 'max:9999'],
            'opening_time' => ['nullable', 'date_format:H:i'],
            'closing_time' => ['nullable', 'date_format:H:i'],
        ];
    }
}
