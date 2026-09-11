<?php

namespace App\Http\Requests\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Throwable;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

    public const SESSION_KEY = 'password_reset_phone';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $raw = $this->input('phone') ?? $this->session()->get(self::SESSION_KEY);

        if (! is_string($raw) || $raw === '') {
            return;
        }

        try {
            $this->merge(['phone' => Phone::toE164($raw)]);
        } catch (Throwable) {
            $this->merge(['phone' => $raw]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function rules(): array
    {
        return [
            'phone' => ['required', 'string', ...Phone::validationRule()],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * @throws ValidationException
     */
    public function resetPassword(): void
    {
        $phone = $this->string('phone')->value();
        $sessionPhone = $this->session()->get(self::SESSION_KEY);

        if (! is_string($sessionPhone) || $sessionPhone !== $phone) {
            throw ValidationException::withMessages([
                'phone' => [__('This password reset session is invalid or has expired.')],
            ]);
        }

        $user = User::query()->where('phone', $phone)->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'phone' => [__('passwords.user')],
            ]);
        }

        app(ResetUserPassword::class)->reset($user, $this->all());

        $this->session()->forget(self::SESSION_KEY);

        Auth::guard($user->guardName())->logout();
    }
}
