<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateContactSettingsRequest extends FormRequest
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
            'phone' => ['nullable', 'string', 'max:60'],
            'fax' => ['nullable', 'string', 'max:60'],
            'email' => ['nullable', 'email', 'max:255'],
            'whatsapp' => ['nullable', 'string', 'max:60'],
            'address' => ['nullable', 'string', 'max:1000'],
            'map_embed_url' => ['nullable', 'string', 'max:2000'],
            'socials' => ['nullable', 'array', 'max:20'],
            'socials.*.key' => ['nullable', 'string', 'max:60'],
            'socials.*.label' => ['required_with:socials.*.url', 'nullable', 'string', 'max:80'],
            'socials.*.url' => ['nullable', 'string', 'max:500'],
            'socials.*.icon' => ['nullable', 'string', 'max:80'],
        ];
    }
}
