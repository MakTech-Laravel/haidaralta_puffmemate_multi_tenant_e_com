<?php

namespace App\Services\Maps\Providers;

use App\Contracts\Maps\Geocodes;
use App\Contracts\Maps\Routes;
use App\Enums\MapCapability;
use App\Services\Maps\Data\GeocodeResult;
use App\Services\Maps\Data\RouteResult;
use RuntimeException;

class OpenStreetMapProvider extends AbstractMapProvider implements Geocodes, Routes
{
    /**
     * Display and geocoding need no key. Routing needs a self-hosted or licensed OSRM endpoint.
     */
    public function capabilities(): array
    {
        $capabilities = [MapCapability::Display, MapCapability::Geocoding];

        if (filled($this->osrmUrl())) {
            $capabilities[] = MapCapability::Routing;
        }

        return $capabilities;
    }

    public function frontendConfig(): array
    {
        return [
            'type' => 'leaflet',
            'provider' => $this->key,
            'tile_url' => (string) $this->credentials->option('tile_url', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
            'attribution' => '&copy; OpenStreetMap contributors',
            'max_zoom' => 19,
        ];
    }

    public function geocode(string $query, array $options = []): array
    {
        $params = [
            'format' => 'jsonv2',
            'q' => $query,
            'limit' => (int) ($options['limit'] ?? 5),
            'addressdetails' => 1,
        ];

        if (filled($country = $this->countryOption($options))) {
            $params['countrycodes'] = $country;
        }

        $response = $this->http()->get($this->nominatimUrl().'/search', $params)->throw();

        return collect($response->json() ?? [])
            ->map(fn (array $item): GeocodeResult => $this->toResult($item))
            ->values()
            ->all();
    }

    public function reverse(float $latitude, float $longitude): ?GeocodeResult
    {
        $response = $this->http()->get($this->nominatimUrl().'/reverse', [
            'format' => 'jsonv2',
            'lat' => $latitude,
            'lon' => $longitude,
            'addressdetails' => 1,
        ])->throw();

        $data = $response->json();

        return is_array($data) && isset($data['lat'], $data['lon']) ? $this->toResult($data) : null;
    }

    public function route(array $origin, array $destination): ?RouteResult
    {
        $base = $this->osrmUrl();

        if (blank($base)) {
            throw new RuntimeException('OSRM routing endpoint is not configured.');
        }

        $coordinates = sprintf('%F,%F;%F,%F', $origin[1], $origin[0], $destination[1], $destination[0]);
        $response = $this->http()->get(rtrim($base, '/').'/route/v1/driving/'.$coordinates, [
            'overview' => 'full',
            'geometries' => 'geojson',
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
     * @param  array<string, mixed>  $item
     */
    private function toResult(array $item): GeocodeResult
    {
        $address = (array) ($item['address'] ?? []);

        return new GeocodeResult(
            label: (string) ($item['display_name'] ?? ''),
            latitude: (float) $item['lat'],
            longitude: (float) $item['lon'],
            provider: $this->key,
            address: [
                'street' => trim(($address['house_number'] ?? '').' '.($address['road'] ?? '')) ?: null,
                'suburb' => $address['suburb'] ?? $address['city_district'] ?? null,
                'city' => $address['city'] ?? $address['town'] ?? $address['village'] ?? null,
                'state' => $address['state'] ?? null,
                'postcode' => $address['postcode'] ?? null,
                'country' => $address['country'] ?? null,
                'country_code' => isset($address['country_code']) ? strtolower((string) $address['country_code']) : null,
            ],
        );
    }

    private function nominatimUrl(): string
    {
        return rtrim((string) $this->credentials->option('nominatim_url', 'https://nominatim.openstreetmap.org'), '/');
    }

    private function osrmUrl(): ?string
    {
        $url = $this->credentials->option('osrm_url');

        return filled($url) ? (string) $url : null;
    }
}
