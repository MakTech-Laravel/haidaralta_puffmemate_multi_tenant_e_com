<?php

namespace App\Http\Requests\Platform;

use App\Services\Application\ApplicationSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateGeneralSettingsRequest extends FormRequest
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
            'app_name' => ['required', 'string', 'max:120'],
            'app_short_name' => ['required', 'string', 'max:60'],
            'short_description' => ['nullable', 'string', 'max:500'],
            'theme_color' => ['required', 'string', 'max:32'],
            'background_color' => ['required', 'string', 'max:32'],
            'display_timezone' => ['required', 'timezone:all'],
            'date_format' => ['required', 'string', Rule::in(ApplicationSettingsService::DATE_FORMAT_PRESETS)],
            'time_format' => ['required', 'string', Rule::in(ApplicationSettingsService::TIME_FORMAT_PRESETS)],
            'logo' => ['nullable', 'image', 'max:2048'],
            'favicon' => ['nullable', 'file', 'mimes:ico,png,svg,jpg,jpeg,webp', 'max:1024'],
            'pwa_icon_192' => ['nullable', 'image', 'max:2048'],
            'pwa_icon_512' => ['nullable', 'image', 'max:4096'],
            'pwa_maskable' => ['nullable', 'image', 'max:4096'],
            'apple_touch_icon' => ['nullable', 'image', 'max:2048'],
            'remove_logo' => ['sometimes', 'boolean'],
            'remove_favicon' => ['sometimes', 'boolean'],
            'remove_pwa_icon_192' => ['sometimes', 'boolean'],
            'remove_pwa_icon_512' => ['sometimes', 'boolean'],
            'remove_pwa_maskable' => ['sometimes', 'boolean'],
            'remove_apple_touch_icon' => ['sometimes', 'boolean'],
            'maintenance_mode' => ['sometimes', 'boolean'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'remove_logo' => $this->boolean('remove_logo'),
            'remove_favicon' => $this->boolean('remove_favicon'),
            'remove_pwa_icon_192' => $this->boolean('remove_pwa_icon_192'),
            'remove_pwa_icon_512' => $this->boolean('remove_pwa_icon_512'),
            'remove_pwa_maskable' => $this->boolean('remove_pwa_maskable'),
            'remove_apple_touch_icon' => $this->boolean('remove_apple_touch_icon'),
            'maintenance_mode' => $this->boolean('maintenance_mode'),
        ]);
    }
}
