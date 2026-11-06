<?php

namespace App\Services\Maps;

use App\Models\MapProvider;
use App\Models\MapSetting;
use App\Services\Maps\Data\MapCredentials;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use InvalidArgumentException;

/**
 * Reads and writes dashboard-managed map configuration.
 *
 * Credential resolution is DB first, then config/maps.php (env). Raw attributes are cached
 * (keys stay ciphertext in the cache) and the cache is rebuilt on every write.
 */
class MapSettingsService
{
    public const PROVIDERS_CACHE_KEY = 'maps.providers';

    public const SETTINGS_CACHE_KEY = 'maps.settings';

    /**
     * @return list<string>
     */
    public function providerKeys(): array
    {
        return array_keys((array) config('maps.providers', []));
    }

    public function hasProvider(string $key): bool
    {
        return in_array($key, $this->providerKeys(), true);
    }

    /**
     * @return Collection<string, MapProvider>
     */
    public function rows(): Collection
    {
        if (! $this->tableReady('map_providers')) {
            return collect();
        }

        /** @var list<array<string, mixed>> $attributes */
        $attributes = Cache::rememberForever(self::PROVIDERS_CACHE_KEY, function (): array {
            $this->sync();

            return MapProvider::query()->get()
                ->map(fn (MapProvider $row): array => $row->getAttributes())
                ->values()
                ->all();
        });

        return collect($attributes)
            ->map(fn (array $row): MapProvider => (new MapProvider)->newFromBuilder($row))
            ->keyBy('key');
    }

    public function row(string $key): ?MapProvider
    {
        return $this->rows()->get($key);
    }

    public function settings(): MapSetting
    {
        if (! $this->tableReady('map_settings')) {
            return $this->defaultSettings();
        }

        /** @var array<string, mixed> $attributes */
        $attributes = Cache::rememberForever(self::SETTINGS_CACHE_KEY, function (): array {
            $this->sync();

            return MapSetting::query()->firstOrFail()->getAttributes();
        });

        return (new MapSetting)->newFromBuilder($attributes);
    }

    public function credentials(string $key): MapCredentials
    {
        $config = (array) config("maps.providers.{$key}", []);
        $row = $this->row($key);

        $options = array_filter(
            array_merge((array) ($config['options'] ?? []), (array) ($row?->options ?? [])),
            fn (mixed $value): bool => filled($value),
        );

        return new MapCredentials(
            publicKey: $this->firstFilled($row?->public_key, $config['env']['public_key'] ?? null),
            serverKey: $this->firstFilled($row?->server_key, $config['env']['server_key'] ?? null),
            options: $options,
        );
    }

    /**
     * Where a key currently comes from: the dashboard (database), the deployment env, or nowhere.
     *
     * @param  'public_key'|'server_key'  $field
     * @return 'dashboard'|'environment'|'none'
     */
    public function keySource(string $key, string $field): string
    {
        if (filled($this->row($key)?->{$field})) {
            return 'dashboard';
        }

        return filled(config("maps.providers.{$key}.env.{$field}")) ? 'environment' : 'none';
    }

    /**
     * Ensures one row per registered provider and the singleton selection row exist.
     */
    public function sync(): void
    {
        if ($this->tableReady('map_providers')) {
            foreach ($this->providerKeys() as $key) {
                MapProvider::query()->firstOrCreate(['key' => $key], ['is_enabled' => true]);
            }
        }

        if ($this->tableReady('map_settings') && ! MapSetting::query()->exists()) {
            MapSetting::query()->create($this->defaultSettings()->only([
                'display_provider',
                'geocoding_provider',
                'routing_provider',
                'fallback_provider',
            ]));
        }
    }

    public function refresh(): void
    {
        Cache::forget(self::PROVIDERS_CACHE_KEY);
        Cache::forget(self::SETTINGS_CACHE_KEY);
    }

    /**
     * A blank key keeps the stored key. Pass clear_public_key / clear_server_key to remove one.
     *
     * @param  array{
     *     is_enabled?: bool,
     *     public_key?: string|null,
     *     server_key?: string|null,
     *     clear_public_key?: bool,
     *     clear_server_key?: bool,
     *     options?: array<string, mixed>|null,
     *     daily_limit?: int|null,
     *     monthly_limit?: int|null,
     * }  $data
     */
    public function updateProvider(string $key, array $data, ?int $updatedBy = null): MapProvider
    {
        $this->assertKnownProvider($key);
        $this->sync();

        $provider = MapProvider::query()->where('key', $key)->firstOrFail();

        if (array_key_exists('is_enabled', $data)) {
            $provider->is_enabled = (bool) $data['is_enabled'];
        }

        if (! empty($data['clear_public_key'])) {
            $provider->public_key = null;
        } elseif (array_key_exists('public_key', $data) && filled($data['public_key'])) {
            $provider->public_key = (string) $data['public_key'];
        }

        if (! empty($data['clear_server_key'])) {
            $provider->server_key = null;
        } elseif (array_key_exists('server_key', $data) && filled($data['server_key'])) {
            $provider->server_key = (string) $data['server_key'];
        }

        if (array_key_exists('options', $data)) {
            $provider->options = $data['options'];
        }

        foreach (['daily_limit', 'monthly_limit'] as $limit) {
            if (array_key_exists($limit, $data)) {
                $provider->{$limit} = filled($data[$limit]) ? (int) $data[$limit] : null;
            }
        }

        $provider->updated_by = $updatedBy;
        $provider->save();

        $this->refresh();

        return $provider;
    }

    /**
     * @param  array{display_provider: string, geocoding_provider: string, routing_provider: string, fallback_provider: string}  $data
     */
    public function updateSelection(array $data, ?int $updatedBy = null): MapSetting
    {
        foreach (['display_provider', 'geocoding_provider', 'routing_provider', 'fallback_provider'] as $field) {
            $this->assertKnownProvider($data[$field]);
        }

        $this->sync();

        $settings = MapSetting::query()->firstOrFail();
        $settings->fill([
            'display_provider' => $data['display_provider'],
            'geocoding_provider' => $data['geocoding_provider'],
            'routing_provider' => $data['routing_provider'],
            'fallback_provider' => $data['fallback_provider'],
            'updated_by' => $updatedBy,
        ])->save();

        $this->refresh();

        return $settings;
    }

    private function defaultSettings(): MapSetting
    {
        $defaults = (array) config('maps.defaults', []);
        $fallback = (string) config('maps.default_provider', 'osm');

        return new MapSetting([
            'display_provider' => $this->knownOr($defaults['display'] ?? null, $fallback),
            'geocoding_provider' => $this->knownOr($defaults['geocoding'] ?? null, $fallback),
            'routing_provider' => $this->knownOr($defaults['routing'] ?? null, $fallback),
            'fallback_provider' => $this->knownOr($defaults['fallback'] ?? null, $fallback),
        ]);
    }

    private function knownOr(mixed $key, string $fallback): string
    {
        return is_string($key) && $this->hasProvider($key) ? $key : $fallback;
    }

    private function assertKnownProvider(string $key): void
    {
        if (! $this->hasProvider($key)) {
            throw new InvalidArgumentException("Unknown map provider [{$key}].");
        }
    }

    private function firstFilled(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            if (filled($value)) {
                return (string) $value;
            }
        }

        return null;
    }

    private function tableReady(string $table): bool
    {
        try {
            return Schema::hasTable($table);
        } catch (\Throwable) {
            return false;
        }
    }
}
