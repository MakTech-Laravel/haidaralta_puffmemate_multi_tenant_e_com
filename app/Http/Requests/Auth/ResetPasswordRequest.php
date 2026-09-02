<?php

namespace App\Http\Requests\Auth;

use App\Actions\Fortify\ResetUserPassword;
use App\Concerns\PasswordValidationRules;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\ValidationException;

class ResetPasswordRequest extends FormRequest
{
    use PasswordValidationRules;

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
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => $this->passwordRules(),
        ];
    }

    /**
     * Reset the user's password on their portal guard broker.
     *
     * @throws ValidationException
     */
    public function resetPassword(): void
    {
        $user = User::query()
            ->where('email', $this->string('email'))
            ->first();

        if ($user === null) {
            throw ValidationException::withMessages([
                'email' => [__('passwords.user')],
            ]);
        }

        $status = Password::broker($user->guardName())->reset(
            $this->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user): void {
                app(ResetUserPassword::class)->reset($user, $this->all());
            },
        );

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages([
                'email' => [__($status)],
            ]);
        }

        Auth::guard($user->guardName())->logout();
    }
}
