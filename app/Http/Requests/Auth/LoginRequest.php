<?php

namespace App\Http\Requests\Auth;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Auth\Events\Lockout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class LoginRequest extends FormRequest
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
            'password' => ['required', 'string'],
            'remember' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * Attempt to authenticate the request's credentials on the user's portal guard.
     *
     * @throws ValidationException
     */
    public function authenticate(): void
    {
        $this->ensureIsNotRateLimited();

        $user = User::query()
            ->with('branch')
            ->where('email', $this->string('email'))
            ->first();

        if ($this->shouldRejectLogin($user)) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        $credentials = [
            'email' => $this->string('email')->value(),
            'password' => $this->string('password')->value(),
        ];

        if (! Auth::guard($user->guardName())->attempt($credentials, $this->boolean('remember'))) {
            $this->recordFailedAttempt();

            throw ValidationException::withMessages([
                'email' => [__('auth.failed')],
            ]);
        }

        RateLimiter::clear($this->throttleKey());
    }

    /**
     * Whether login should fail before attempting credentials.
     */
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
     * Ensure the login request is not rate limited.
     *
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
            'email' => [__('auth.throttle', [
                'seconds' => $seconds,
                'minutes' => (int) ceil($seconds / 60),
            ])],
        ])->status(429);
    }

    private function recordFailedAttempt(): void
    {
        RateLimiter::hit($this->throttleKey(), 60);
    }

    /**
     * Get the rate limiting throttle key for the request.
     */
    public function throttleKey(): string
    {
        return Str::transliterate(Str::lower($this->string('email')->value()).'|'.$this->ip());
    }
}
