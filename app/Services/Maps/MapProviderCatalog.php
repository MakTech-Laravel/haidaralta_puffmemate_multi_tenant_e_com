<?php

namespace App\Services\Maps;

use App\Contracts\Maps\MapProvider;

/**
 * Builds provider instances from the code-defined registry in config/maps.php.
 */
class MapProviderCatalog
{
    public function __construct(private readonly MapSettingsService $settings) {}

    public function make(string $key): ?MapProvider
    {
        $config = config("maps.providers.{$key}");

        if (! is_array($config) || ! is_a($config['class'] ?? '', MapProvider::class, true)) {
            return null;
        }

        $class = $config['class'];

        return new $class(
            $key,
            (string) ($config['label'] ?? ucfirst($key)),
            $this->settings->credentials($key),
        );
    }

    /**
     * @return list<MapProvider>
     */
    public function all(): array
    {
        return collect($this->settings->providerKeys())
            ->map(fn (string $key): ?MapProvider => $this->make($key))
            ->filter()
            ->values()
            ->all();
    }
}
