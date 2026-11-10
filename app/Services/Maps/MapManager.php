<?php

namespace App\Services\Maps;

use App\Contracts\Maps\Geocodes;
use App\Contracts\Maps\Routes;
use App\Enums\MapCapability;
use App\Services\Maps\Data\GeocodeResult;
use App\Services\Maps\Data\RouteResult;
use App\Services\Maps\Exceptions\MapProviderUnavailableException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Single entry point for map features. Every billable lookup is cached first, counted
 * against the provider's quota, and retried on the next usable provider when it fails.
 */
class MapManager
{
    public function __construct(
        private readonly MapProviderResolver $resolver,
        private readonly MapUsageRecorder $usage,
    ) {}

    /**
     * Browser-safe renderer configuration for the active display provider.
     *
     * @return array<string, mixed>
     */
    public function displayConfig(): array
    {
        $provider = $this->resolver->resolve(MapCapability::Display);

        return ['key' => $provider->key(), 'label' => $provider->label()] + $provider->frontendConfig();
    }

    /**
     * Tile settings for tools built on Leaflet only (the area polygon editor). Google tiles
     * cannot be used in Leaflet, so a Google display provider falls back to the next
     * Leaflet-capable provider in the chain (the fallback selection, then OpenStreetMap).
     *
     * @return array<string, mixed>
     */
    public function leafletDisplayConfig(): array
    {
        foreach ($this->resolver->chain(MapCapability::Display) as $provider) {
            $config = $provider->frontendConfig();

            if (($config['type'] ?? null) === 'leaflet') {
                return ['key' => $provider->key(), 'label' => $provider->label()] + $config;
            }
        }

        throw MapProviderUnavailableException::forCapability(MapCapability::Display);
    }

    /**
     * @param  array{country?: string|null, limit?: int}  $options
     * @return list<GeocodeResult>
     */
    public function geocode(string $query, array $options = []): array
    {
        $normalized = $this->normalizeQuery($query);

        if ($normalized === '') {
            return [];
        }

        $cacheKey = 'maps.geocode.'.sha1($normalized.'|'.json_encode($options));

        /** @var list<array<string, mixed>>|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return array_map(fn (array $item): GeocodeResult => GeocodeResult::fromArray($item), $cached);
        }

        $results = $this->attempt(
            MapCapability::Geocoding,
            Geocodes::class,
            fn (Geocodes $provider): array => $provider->geocode($query, $options),
        );

        if ($results !== []) {
            Cache::put(
                $cacheKey,
                array_map(fn (GeocodeResult $result): array => $result->toArray(), $results),
                now()->addDays((int) config('maps.cache.geocode_ttl_days', 30)),
            );
        }

        return $results;
    }

    public function reverse(float $latitude, float $longitude): ?GeocodeResult
    {
        $cacheKey = sprintf('maps.reverse.%.5f,%.5f', $latitude, $longitude);

        /** @var array<string, mixed>|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return GeocodeResult::fromArray($cached);
        }

        $result = $this->attempt(
            MapCapability::Geocoding,
            Geocodes::class,
            fn (Geocodes $provider): ?GeocodeResult => $provider->reverse($latitude, $longitude),
        );

        if ($result !== null) {
            Cache::put($cacheKey, $result->toArray(), now()->addDays((int) config('maps.cache.geocode_ttl_days', 30)));
        }

        return $result;
    }

    /**
     * @param  array{0: float, 1: float}  $origin  [latitude, longitude]
     * @param  array{0: float, 1: float}  $destination  [latitude, longitude]
     */
    public function route(array $origin, array $destination): ?RouteResult
    {
        $cacheKey = sprintf(
            'maps.route.%.4f,%.4f>%.4f,%.4f',
            $origin[0], $origin[1], $destination[0], $destination[1],
        );

        /** @var array{distance_meters: int, duration_seconds: int, points: list<array{0: float, 1: float}>, provider: string}|null $cached */
        $cached = Cache::get($cacheKey);

        if (is_array($cached)) {
            return RouteResult::fromArray($cached);
        }

        $result = $this->attempt(
            MapCapability::Routing,
            Routes::class,
            fn (Routes $provider): ?RouteResult => $provider->route($origin, $destination),
        );

        if ($result !== null) {
            Cache::put($cacheKey, $result->toArray(), now()->addMinutes((int) config('maps.cache.route_ttl_minutes', 10)));
        }

        return $result;
    }

    /**
     * Runs a billable call on each usable provider in turn until one succeeds.
     *
     * @template TResult
     *
     * @param  class-string  $contract
     * @param  callable(object): TResult  $call
     * @return TResult
     */
    private function attempt(MapCapability $capability, string $contract, callable $call): mixed
    {
        $lastError = null;

        foreach ($this->resolver->chain($capability) as $provider) {
            if (! $provider instanceof $contract) {
                continue;
            }

            try {
                $result = $call($provider);
            } catch (Throwable $e) {
                $lastError = $e->getMessage();
                Log::warning('Map provider call failed.', [
                    'provider' => $provider->key(),
                    'capability' => $capability->value,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            $this->usage->record($provider->key(), $capability);

            return $result;
        }

        throw MapProviderUnavailableException::forCapability($capability, $lastError);
    }

    private function normalizeQuery(string $query): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/u', ' ', $query)));
    }
}
