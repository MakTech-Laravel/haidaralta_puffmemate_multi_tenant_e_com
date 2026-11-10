<?php

namespace App\Services\Maps;

use App\Enums\MapCapability;

/**
 * The one-step "Save and use" flow of the map settings page: save the key, check that the
 * provider really accepts it, and only then switch every feature the provider can serve over to
 * it. A wrong key never changes what is in use, so maps keep working.
 */
class MapProviderActivator
{
    public function __construct(
        private readonly MapSettingsService $settings,
        private readonly MapProviderCatalog $catalog,
        private readonly MapUsageRecorder $usage,
    ) {}

    /**
     * @param  array{public_key?: string|null, server_key?: string|null, daily_limit?: int|string|null}  $data
     * @return array{ok: bool, message: string}
     */
    public function activate(string $key, array $data, ?int $updatedBy = null): array
    {
        $label = (string) config("maps.providers.{$key}.label", $key);
        $default = (string) config('maps.default_provider', 'osm');

        $update = ['is_enabled' => true];

        foreach (['public_key', 'server_key'] as $field) {
            if (filled($data[$field] ?? null)) {
                $update[$field] = $data[$field];
            }
        }

        if (array_key_exists('daily_limit', $data)) {
            $update['daily_limit'] = $data['daily_limit'];
        }

        $this->settings->updateProvider($key, $update, $updatedBy);

        $provider = $this->catalog->make($key);

        if ($provider === null || ! $provider->isConfigured()) {
            return ['ok' => false, 'message' => "{$label} needs a key before it can be used. Paste your key and try again."];
        }

        // OpenStreetMap is the safe default and needs no key, so there is nothing to check.
        if ($key !== $default && $provider->supports(MapCapability::Geocoding)) {
            $check = $provider->testConnection();
            $this->usage->record($key, MapCapability::Geocoding);

            if (! $check['ok']) {
                return [
                    'ok' => false,
                    'message' => "We saved the key, but {$label} did not accept it. Check that you copied the whole key and that every service listed in the steps is turned on. Nothing was switched, so your maps keep working as before.",
                ];
            }
        }

        $uses = fn (MapCapability $capability): string => $provider->supports($capability) ? $key : $default;

        $this->settings->updateSelection([
            'display_provider' => $uses(MapCapability::Display),
            'geocoding_provider' => $uses(MapCapability::Geocoding),
            'routing_provider' => $uses(MapCapability::Routing),
            'fallback_provider' => $default,
        ], $updatedBy);

        return ['ok' => true, 'message' => $this->summary($key, $label, $provider->capabilities())];
    }

    /**
     * @param  list<MapCapability>  $supported
     */
    private function summary(string $key, string $label, array $supported): string
    {
        $words = [
            MapCapability::Display->value => 'the map',
            MapCapability::Geocoding->value => 'address search',
            MapCapability::Routing->value => 'routes',
        ];

        $used = [];
        $missing = [];

        foreach (MapCapability::cases() as $capability) {
            if (in_array($capability, $supported, true)) {
                $used[] = $words[$capability->value];
            } else {
                $missing[] = $words[$capability->value];
            }
        }

        $message = "{$label} is now used for ".$this->join($used).'.';

        if ($missing !== []) {
            $message .= $key === config('maps.default_provider')
                ? ' Drivers see straight-line distances instead of road routes.'
                : ' '.ucfirst($this->join($missing)).' still use OpenStreetMap until that key is added.';
        }

        return $message;
    }

    /**
     * @param  list<string>  $items
     */
    private function join(array $items): string
    {
        return count($items) > 1
            ? implode(', ', array_slice($items, 0, -1)).' and '.end($items)
            : ($items[0] ?? 'nothing');
    }
}
