<?php

namespace App\Http\Requests\Auth;

use App\Enums\OtpPurpose;
use App\Services\OtpService;
use Illuminate\Foundation\Http\FormRequest;

class VerifyPhoneRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6'],
        ];
    }

    public function verify(OtpService $otpService): void
    {
        $user = $this->user();

        $otpService->verify(
            $user->phone,
            OtpPurpose::PhoneVerify,
            $this->string('code')->value(),
        );

        $user->forceFill([
            'phone_verified_at' => now(),
        ])->save();
    }
}
