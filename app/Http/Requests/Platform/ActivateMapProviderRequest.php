<?php

namespace App\Http\Requests\Platform;

use App\Services\Maps\MapSettingsService;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ActivateMapProviderRequest extends FormRequest
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
            'provider' => ['required', 'string', Rule::in(app(MapSettingsService::class)->providerKeys())],
            'public_key' => ['nullable', 'string', 'max:512'],
            'server_key' => ['nullable', 'string', 'max:512'],
            'daily_limit' => ['nullable', 'integer', 'min:1', 'max:100000000'],
        ];
    }
}
