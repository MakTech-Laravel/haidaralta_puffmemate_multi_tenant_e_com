<?php

namespace App\Http\Requests\Customer;

use App\Concerns\NormalizesPhoneInput;
use App\Concerns\ProfileValidationRules;
use App\Enums\AustralianState;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerProfileRequest extends FormRequest
{
    use NormalizesPhoneInput;
    use ProfileValidationRules;

    public function authorize(): bool
    {
        return $this->user()?->user_type === UserType::CUSTOMER;
    }

    protected function prepareForValidation(): void
    {
        $this->mergeNormalizedPhone();
        $this->normalizeBlankEmailToNull();

        foreach (['region', 'city', 'zip', 'address'] as $field) {
            if ($this->input($field) === '') {
                $this->merge([$field => null]);
            }
        }
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            ...$this->profileRules($this->user()?->id),
            'region' => ['nullable', Rule::enum(AustralianState::class)],
            'city' => ['nullable', 'string', 'max:255'],
            'zip' => ['nullable', 'string', 'max:16'],
            'address' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
