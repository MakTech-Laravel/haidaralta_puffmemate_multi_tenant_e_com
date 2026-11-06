<?php

namespace App\Http\Requests\Platform;

use App\Enums\MapCapability;
use App\Services\Maps\MapProviderCatalog;
use App\Services\Maps\MapSettingsService;
use Closure;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateMapSelectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Each capability needs a provider that is registered in code and currently has the keys for it.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $known = app(MapSettingsService::class)->providerKeys();

        $supports = fn (MapCapability $capability): Closure => function (string $attribute, mixed $value, Closure $fail) use ($capability): void {
            $provider = app(MapProviderCatalog::class)->make((string) $value);

            if ($provider === null || ! $provider->supports($capability)) {
                $fail('This provider has no key saved for '.strtolower($capability->label()).'.');
            }
        };

        return [
            'display_provider' => ['required', 'string', Rule::in($known), $supports(MapCapability::Display)],
            'geocoding_provider' => ['required', 'string', Rule::in($known), $supports(MapCapability::Geocoding)],
            'routing_provider' => ['required', 'string', Rule::in($known), $supports(MapCapability::Routing)],
            'fallback_provider' => ['required', 'string', Rule::in($known)],
        ];
    }
}
