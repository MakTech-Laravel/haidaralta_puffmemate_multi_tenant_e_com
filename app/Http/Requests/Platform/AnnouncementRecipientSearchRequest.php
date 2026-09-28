<?php

namespace App\Http\Requests\Platform;

use App\Enums\AnnouncementAudience;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnnouncementRecipientSearchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'audience' => ['required', Rule::enum(AnnouncementAudience::class)],
            'q' => ['nullable', 'string', 'max:80'],
        ];
    }
}
