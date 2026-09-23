<?php

namespace App\Http\Requests\Platform;

use Illuminate\Foundation\Http\FormRequest;

class StoreHomeBannerRequest extends FormRequest
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
            'is_active' => ['required', 'boolean'],
            'eyebrow' => ['required', 'string', 'max:120'],
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:500'],
            'primary_cta_label' => ['required', 'string', 'max:60'],
            'primary_cta_href' => ['required', 'string', 'max:255'],
            'secondary_cta_label' => ['required', 'string', 'max:60'],
            'secondary_cta_href' => ['required', 'string', 'max:255'],
            'image' => ['required', 'file', 'image', 'max:5120'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
