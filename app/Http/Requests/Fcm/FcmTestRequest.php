<?php

namespace App\Http\Requests\Fcm;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class FcmTestRequest extends FormRequest
{
    public const MAX_BATCH = 50;

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:2000'],
            'mode' => ['required', Rule::in(['token', 'user'])],
            'token' => ['nullable', 'string', 'max:4096'],
            'tokens' => ['nullable', 'array', 'max:'.self::MAX_BATCH],
            'tokens.*' => ['string', 'min:20', 'max:4096'],
            'user_ids' => ['nullable', 'array', 'max:'.self::MAX_BATCH],
            'user_ids.*' => ['integer', 'exists:users,id'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $mode = (string) $this->input('mode');

            if ($mode === 'token' && $this->resolvedTokens() === []) {
                $validator->errors()->add('tokens', __('Provide at least one FCM token.'));
            }

            if ($mode === 'user' && $this->resolvedUserIds() === []) {
                $validator->errors()->add('user_ids', __('Provide at least one user id.'));
            }
        });
    }

    /**
     * @return list<string>
     */
    public function resolvedTokens(): array
    {
        $tokens = collect($this->input('tokens', []));

        if ($this->filled('token')) {
            $tokens->push($this->string('token')->toString());
        }

        return $tokens
            ->filter(fn (mixed $token): bool => is_string($token) && trim($token) !== '')
            ->map(fn (string $token): string => trim($token))
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @return list<int>
     */
    public function resolvedUserIds(): array
    {
        return collect($this->input('user_ids', []))
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values()
            ->all();
    }
}
