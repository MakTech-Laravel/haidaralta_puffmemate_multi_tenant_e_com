<?php

namespace App\Http\Requests\Fcm;

use Illuminate\Foundation\Http\FormRequest;

class FcmUnregisterRequest extends FormRequest
{
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
            'token' => ['required', 'string', 'min:20', 'max:4096'],
        ];
    }
}
