<?php

namespace App\Http\Requests\Auth;

use App\Concerns\PasswordValidationRules;
use App\Concerns\ProfileValidationRules;
use App\Enums\CustomerRegistrationStatus;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Throwable;

class StoreRegisteredUserRequest extends FormRequest
{
    use PasswordValidationRules, ProfileValidationRules;

    public function authorize(): bool
    {
        return true;
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
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'name' => $this->nameRules(),
            'phone' => [
                'required',
                'string',
                ...Phone::validationRule(),
                Rule::unique(User::class, 'phone')->where(
                    fn ($query) => $query->where(
                        fn ($query) => $query
                            ->where('registration_status', '!=', CustomerRegistrationStatus::Provisional->value)
                            ->orWhereNull('registration_status'),
                    ),
                ),
            ],
            'password' => $this->passwordRules(),
        ];
    }
}
