<?php

namespace App\Http\Requests\Platform;

use App\Services\Maps\MapSettingsService;
use Illuminate\Foundation\Http\FormRequest;

class UpdateMapProviderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return app(MapSettingsService::class)->hasProvider((string) $this->route('provider'))
            || abort(404);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $optionNames = array_keys((array) config('maps.providers.'.$this->route('provider').'.option_labels', []));

        $rules = [
            'is_enabled' => ['required', 'boolean'],
            'public_key' => ['nullable', 'string', 'max:512'],
            'server_key' => ['nullable', 'string', 'max:512'],
            'clear_public_key' => ['sometimes', 'boolean'],
            'clear_server_key' => ['sometimes', 'boolean'],
            'options' => ['nullable', 'array'],
            'daily_limit' => ['nullable', 'integer', 'min:1', 'max:100000000'],
            'monthly_limit' => ['nullable', 'integer', 'min:1', 'max:100000000'],
        ];

        foreach ($optionNames as $name) {
            $rules["options.{$name}"] = ['nullable', 'string', 'max:255'];
        }

        return $rules;
    }

    protected function prepareForValidation(): void
    {
        $allowed = array_keys((array) config('maps.providers.'.$this->route('provider').'.option_labels', []));

        $this->merge([
            'is_enabled' => $this->boolean('is_enabled'),
            'clear_public_key' => $this->boolean('clear_public_key'),
            'clear_server_key' => $this->boolean('clear_server_key'),
            'options' => collect((array) $this->input('options', []))
                ->only($allowed)
                ->map(fn (mixed $value): ?string => filled($value) ? trim((string) $value) : null)
                ->filter()
                ->all(),
        ]);
    }
}
