<?php

namespace App\Http\Requests\Settings;

use App\Concerns\ProfileValidationRules;
use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class ProfileUpdateRequest extends FormRequest
{
    use ProfileValidationRules;

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

        if ($this->input('email') === '') {
            $this->merge(['email' => null]);
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()->id);
    }
}
