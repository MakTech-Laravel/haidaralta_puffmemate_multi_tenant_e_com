<?php

namespace App\Services\Maps;

use App\Contracts\Maps\MapProvider;
use App\Enums\MapCapability;
use App\Services\Maps\Exceptions\MapProviderUnavailableException;

/**
 * Chooses which provider serves a capability: the dashboard selection, then the fallback,
 * then the code default. Unconfigured, disabled or over-quota providers are skipped.
 */
class MapProviderResolver
{
    public function __construct(
        private readonly MapSettingsService $settings,
        private readonly MapProviderCatalog $catalog,
        private readonly MapUsageRecorder $usage,
    ) {}

    public function resolve(MapCapability $capability): MapProvider
    {
        return $this->chain($capability)[0]
            ?? throw MapProviderUnavailableException::forCapability($capability);
    }

    /**
     * Usable providers in priority order, so callers can retry the next one on failure.
     *
     * @return list<MapProvider>
     */
    public function chain(MapCapability $capability): array
    {
        $selection = $this->settings->settings();

        $primary = match ($capability) {
            MapCapability::Display => $selection->display_provider,
            MapCapability::Geocoding => $selection->geocoding_provider,
            MapCapability::Routing => $selection->routing_provider,
        };

        $keys = array_values(array_unique([
            $primary,
            $selection->fallback_provider,
            (string) config('maps.default_provider', 'osm'),
        ]));

        $providers = [];

        foreach ($keys as $key) {
            $provider = $this->catalog->make($key);

            if ($provider !== null && $this->isUsable($provider, $capability)) {
                $providers[] = $provider;
            }
        }

        return $providers;
    }

    private function isUsable(MapProvider $provider, MapCapability $capability): bool
    {
        if (! $provider->supports($capability)) {
            return false;
        }

        $row = $this->settings->row($provider->key());

        if ($row !== null && ! $row->is_enabled) {
            return false;
        }

        if ($capability->isServerBilled() && $row !== null) {
            if ($row->daily_limit !== null && $this->usage->dailyTotal($provider->key()) >= $row->daily_limit) {
                return false;
            }

            if ($row->monthly_limit !== null && $this->usage->monthlyTotal($provider->key()) >= $row->monthly_limit) {
                return false;
            }
        }

        return true;
    }
}
