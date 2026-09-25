<?php

namespace App\Http\Requests\Customer;

use App\Concerns\NormalizesPhoneInput;
use App\Enums\AustralianState;
use App\Enums\UserType;
use App\Support\Phone;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerBillingAddressRequest extends FormRequest
{
    use NormalizesPhoneInput;

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
        return [
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:2000'],
            'region' => ['required', Rule::enum(AustralianState::class)],
            'city' => ['required', 'string', 'max:255'],
            'zip' => ['required', 'string', 'max:16'],
            'email' => ['nullable', 'string', 'email', 'max:255'],
            'phone' => ['required', 'string', ...Phone::validationRule()],
        ];
    }
}
