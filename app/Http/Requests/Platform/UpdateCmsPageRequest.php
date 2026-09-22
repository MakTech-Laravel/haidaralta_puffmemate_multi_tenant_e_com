<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCmsPageRequest extends FormRequest
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
            'title' => ['required', 'string', 'max:160'],
            'meta_title' => ['nullable', 'string', 'max:160'],
            'meta_description' => ['nullable', 'string', 'max:500'],
            'is_published' => ['required', 'boolean'],
            'payload' => ['required', 'array'],
            'hero_image' => ['nullable', 'file', 'image', 'max:5120'],
            'media_image' => ['nullable', 'file', 'image', 'max:5120'],
            'remove_hero_image' => ['sometimes', 'boolean'],
            'remove_media_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $payload = $this->input('payload');

        if (is_string($payload)) {
            $decoded = json_decode($payload, true);
            if (is_array($decoded)) {
                $this->merge(['payload' => $decoded]);
            }
        }

        $this->merge([
            'is_published' => $this->boolean('is_published'),
            'remove_hero_image' => $this->boolean('remove_hero_image'),
            'remove_media_image' => $this->boolean('remove_media_image'),
        ]);
    }
}
