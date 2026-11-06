<?php

namespace App\Services\Maps\Providers;

use App\Contracts\Maps\Geocodes;
use App\Contracts\Maps\Routes;
use App\Enums\MapCapability;
use App\Services\Maps\Data\GeocodeResult;
use App\Services\Maps\Data\RouteResult;

class MapboxProvider extends AbstractMapProvider implements Geocodes, Routes
{
    private const API = 'https://api.mapbox.com';

    /**
     * Mapbox public tokens work for every API, so either key unlocks the server calls.
     */
    public function capabilities(): array
    {
        $capabilities = [];

        if (filled($this->credentials->publicKey)) {
            $capabilities[] = MapCapability::Display;
        }

        if (filled($this->serverToken())) {
            $capabilities[] = MapCapability::Geocoding;
            $capabilities[] = MapCapability::Routing;
        }

        return $capabilities;
    }

    public function frontendConfig(): array
    {
        $style = (string) $this->credentials->option('style', 'mapbox/streets-v12');

        return [
            'type' => 'leaflet',
            'provider' => $this->key,
            'tile_url' => self::API.'/styles/v1/'.$style.'/tiles/256/{z}/{x}/{y}{r}?access_token='.$this->credentials->publicKey,
            'attribution' => '&copy; Mapbox &copy; OpenStreetMap contributors',
            'max_zoom' => 22,
        ];
    }

    public function geocode(string $query, array $options = []): array
    {
        $params = [
            'q' => $query,
            'limit' => (int) ($options['limit'] ?? 5),
            'access_token' => $this->serverToken(),
        ];

        if (filled($country = $this->countryOption($options))) {
            $params['country'] = $country;
        }

        $response = $this->http()->get(self::API.'/search/geocode/v6/forward', $params)->throw();

        return collect($response->json('features') ?? [])
            ->map(fn (array $feature): ?GeocodeResult => $this->toResult($feature))
            ->filter()
            ->values()
            ->all();
    }

    public function reverse(float $latitude, float $longitude): ?GeocodeResult
    {
        $response = $this->http()->get(self::API.'/search/geocode/v6/reverse', [
            'latitude' => $latitude,
            'longitude' => $longitude,
            'limit' => 1,
            'access_token' => $this->serverToken(),
        ])->throw();

        $feature = $response->json('features.0');

        return is_array($feature) ? $this->toResult($feature) : null;
    }

    public function route(array $origin, array $destination): ?RouteResult
    {
        $coordinates = sprintf('%F,%F;%F,%F', $origin[1], $origin[0], $destination[1], $destination[0]);

        $response = $this->http()->get(self::API.'/directions/v5/mapbox/driving/'.$coordinates, [
            'geometries' => 'geojson',
            'overview' => 'full',
            'access_token' => $this->serverToken(),
        ])->throw();

        $route = $response->json('routes.0');

        if (! is_array($route)) {
            return null;
        }

        return new RouteResult(
            distanceMeters: (int) round((float) $route['distance']),
            durationSeconds: (int) round((float) $route['duration']),
            points: array_map(
                fn (array $pair): array => [(float) $pair[1], (float) $pair[0]],
                $route['geometry']['coordinates'] ?? [],
            ),
            provider: $this->key,
        );
    }

    /**
     * @param  array<string, mixed>  $feature
     */
    private function toResult(array $feature): ?GeocodeResult
    {
        $properties = (array) ($feature['properties'] ?? []);
        $coordinates = (array) ($properties['coordinates'] ?? []);
        $geometry = (array) ($feature['geometry']['coordinates'] ?? []);

        $latitude = $coordinates['latitude'] ?? ($geometry[1] ?? null);
        $longitude = $coordinates['longitude'] ?? ($geometry[0] ?? null);

        if ($latitude === null || $longitude === null) {
            return null;
        }

        $context = (array) ($properties['context'] ?? []);

        return new GeocodeResult(
            label: (string) ($properties['full_address'] ?? $properties['name'] ?? ''),
            latitude: (float) $latitude,
            longitude: (float) $longitude,
            provider: $this->key,
            address: [
                'street' => $context['address']['name'] ?? $properties['name'] ?? null,
                'suburb' => $context['neighborhood']['name'] ?? $context['locality']['name'] ?? null,
                'city' => $context['place']['name'] ?? null,
                'state' => $context['region']['name'] ?? null,
                'postcode' => $context['postcode']['name'] ?? null,
                'country' => $context['country']['name'] ?? null,
            ],
        );
    }

    private function serverToken(): ?string
    {
        $token = $this->credentials->serverKey ?: $this->credentials->publicKey;

        return filled($token) ? $token : null;
    }
}
