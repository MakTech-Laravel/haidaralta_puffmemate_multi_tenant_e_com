<?php

namespace App\Http\Requests\Auth;

use App\Enums\PhoneOtpPurpose;
use App\Enums\UserType;
use App\Models\User;
use App\Services\PhoneOtpService;
use App\Support\Phone;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ForgotPasswordRequest extends FormRequest
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
        ];
    }

    /**
     * Send an OTP when the account exists and is eligible. Always silent about existence.
     */
    public function sendResetOtp(PhoneOtpService $otpService): void
    {
        $this->ensureIsNotRateLimited();

        $phone = $this->string('phone')->value();

        $user = User::query()
            ->with('branch')
            ->where('phone', $phone)
            ->first();

        if ($this->shouldSendOtp($user)) {
            $otpService->issue($phone, PhoneOtpPurpose::PasswordReset);
        }

        RateLimiter::hit($this->throttleKey(), 600);
    }

    private function shouldSendOtp(?User $user): bool
    {
        if ($user === null || ! $user->is_active) {
            return false;
        }

        if ($user->user_type === UserType::BRANCH && $user->branch === null) {
            return false;
        }

        return true;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 3)) {
            return;
        }

        event(new Lockout($this));

        $seconds = RateLimiter::availableIn($this->throttleKey());

        throw ValidationException::withMessages([
            'phone' => [__('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ])],
        ])->status(429);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('phone')->value()).'|'.$this->ip());
    }
}
