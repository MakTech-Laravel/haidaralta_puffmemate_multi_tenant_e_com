<?php

namespace App\Services\Maps\Providers;

use App\Contracts\Maps\Geocodes;
use App\Contracts\Maps\MapProvider;
use App\Enums\MapCapability;
use App\Services\Maps\Data\MapCredentials;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Throwable;

abstract class AbstractMapProvider implements MapProvider
{
    public function __construct(
        protected string $key,
        protected string $label,
        protected MapCredentials $credentials,
    ) {}

    public function key(): string
    {
        return $this->key;
    }

    public function label(): string
    {
        return $this->label;
    }

    public function supports(MapCapability $capability): bool
    {
        return in_array($capability, $this->capabilities(), true);
    }

    public function isConfigured(): bool
    {
        return $this->capabilities() !== [];
    }

    public function testConnection(): array
    {
        if (! $this->isConfigured()) {
            return ['ok' => false, 'message' => 'No API key is configured for this provider.'];
        }

        if ($this->supports(MapCapability::Geocoding) && $this instanceof Geocodes) {
            try {
                $results = $this->geocode('Sydney NSW', ['limit' => 1]);
            } catch (Throwable $e) {
                return ['ok' => false, 'message' => $e->getMessage()];
            }

            return $results === []
                ? ['ok' => false, 'message' => 'The provider answered but returned no results.']
                : ['ok' => true, 'message' => 'Connection works (test address lookup succeeded).'];
        }

        return ['ok' => true, 'message' => 'Browser key is saved. It cannot be verified from the server.'];
    }

    protected function http(): PendingRequest
    {
        return Http::timeout((int) config('maps.http_timeout', 5))
            ->acceptJson()
            ->withUserAgent((string) config('app.name', 'PuffMeMate').' map client');
    }

    protected function countryOption(array $options): ?string
    {
        $country = $options['country'] ?? config('maps.default_country');

        return filled($country) ? strtolower((string) $country) : null;
    }
}
