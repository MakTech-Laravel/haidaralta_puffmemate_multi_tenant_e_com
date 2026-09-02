<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ForgotPasswordRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'email' => ['required', 'string', 'email'],
        ];
    }

    /**
     * Send a reset link when the account exists and is eligible.
     */
    public function sendResetLink(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->with('branch')
            ->where('email', $this->string('email'))
            ->first();

        if ($this->shouldSendResetLink($user)) {
            Password::broker($user->guardName())->sendResetLink([
                'email' => $this->string('email')->value(),
            ]);
        }

        RateLimiter::hit($this->throttleKey(), 600);
    }

    /**
     * Whether a reset link should be sent for this user.
     */
    private function shouldSendResetLink(?User $user): bool
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
     * Ensure the request is not rate limited.
     *
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
            'email' => [__('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ])],
        ])->status(429);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->value()).'|'.$this->ip());
    }
}
