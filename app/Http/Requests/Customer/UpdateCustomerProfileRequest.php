<?php

namespace App\Http\Requests\Customer;

use App\Concerns\NormalizesPhoneInput;
use App\Concerns\ProfileValidationRules;
use App\Enums\UserType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

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
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return $this->profileRules($this->user()?->id);
    }
}
