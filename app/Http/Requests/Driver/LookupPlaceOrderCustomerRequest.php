<?php

namespace App\Http\Requests\Driver;

use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class LookupPlaceOrderCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user('driver') !== null;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('phone');

        if (is_string($raw) && $raw !== '') {
            try {
                $this->merge(['phone' => Phone::toE164($raw)]);
            } catch (Throwable) {
                // Leave raw value for the phone validation rule.
            }
        }
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', ...Phone::validationRule()],
        ];
    }
}
