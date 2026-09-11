<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('remember')) {
            $this->merge([
                'remember' => $this->boolean('remember'),
            ]);
        }

        $this->mergeNormalizedPhone();
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', ...Phone::validationRule()],
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $phone = $this->string('phone')->value();

        $user = User::query()
            ->with('branch')
            ->where('phone', $phone)
            ->first();

        if ($this->shouldRejectLogin($user)) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'phone' => [__('auth.failed')],
            ]);
        }

        $credentials = [
            'phone' => $phone,
            'password' => $this->string('password')->value(),
        ];

        if (! Auth::guard($user->guardName())->attempt($credentials, $this->boolean('remember'))) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'phone' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    private function shouldRejectLogin(?User $user): bool
    {
        if ($user === null || ! $user->is_active) {
            return true;
        }

        if ($user->user_type === UserType::BRANCH && $user->branch === null) {
            return true;
        }

        return false;
    }

    /**
     * @throws ValidationException
     */
    public function ensureIsNotRateLimited(): void
    {
        if (! RateLimiter::tooManyAttempts($this->throttleKey(), 5)) {
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

    private function recordFailedAttempt(): void
    {
        RateLimiter::hit($this->throttleKey(), 60);
    }

    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('phone')->value()).'|'.$this->ip());
    }

    private function mergeNormalizedPhone(): void
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
}
