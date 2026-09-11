<?php

namespace App\Http\Requests\Auth;

use App\Enums\PhoneOtpPurpose;
use App\Services\PhoneOtpService;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Throwable;

class VerifyPasswordOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('phone');

        if (! is_string($raw) || $raw === '') {
            return;
        }

        try {
            $this->merge(['phone' => Phone::toE164($raw)]);
        } catch (Throwable) {
            // Leave raw value for the phone validation rule.
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', ...Phone::validationRule()],
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function verify(PhoneOtpService $otpService): void
    {
        $otpService->verify(
            $this->string('phone')->value(),
            PhoneOtpPurpose::PasswordReset,
            $this->string('code')->value(),
        );
    }
}
