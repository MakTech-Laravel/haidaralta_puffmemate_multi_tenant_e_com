<?php

namespace App\Http\Requests\Platform;

use App\Models\HomeBanner;
use Illuminate\Foundation\Http\FormRequest;

class UpdateHomeBannerRequest extends FormRequest
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
            'image' => ['nullable', 'file', 'image', 'max:5120'],
            'remove_image' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'is_active' => $this->boolean('is_active'),
            'remove_image' => $this->boolean('remove_image'),
        ]);
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator): void {
            if (! $this->boolean('is_active')) {
                return;
            }

            /** @var HomeBanner $banner */
            $banner = $this->route('home_banner');
            $removing = $this->boolean('remove_image');
            $hasUpload = $this->file('image') !== null;
            $hasExisting = filled($banner->image_path) && ! $removing;

            if (! $hasUpload && ! $hasExisting) {
                $validator->errors()->add(
                    'image',
                    'An image is required for active banners.',
                );
            }
        });
    }
}
