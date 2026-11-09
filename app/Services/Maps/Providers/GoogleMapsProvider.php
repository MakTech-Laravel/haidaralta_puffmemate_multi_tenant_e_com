<?php

namespace App\Services\Maps\Providers;

use App\Contracts\Maps\Geocodes;
use App\Contracts\Maps\Routes;
use App\Enums\MapCapability;
use App\Services\Maps\Data\GeocodeResult;
use App\Services\Maps\Data\RouteResult;
use App\Support\Geo\EncodedPolyline;
use Illuminate\Http\Client\Response;
use RuntimeException;

class GoogleMapsProvider extends AbstractMapProvider implements Geocodes, Routes
{
    private const GEOCODE_URL = 'https://maps.googleapis.com/maps/api/geocode/json';

    private const ROUTES_URL = 'https://routes.googleapis.com/directions/v2:computeRoutes';

    /**
     * The browser key (referrer restricted) drives the map. The server key (IP restricted) is
     * required for geocoding and routing; the browser key is never used from the backend.
     */
    public function capabilities(): array
    {
        $capabilities = [];

        if (filled($this->credentials->publicKey)) {
            $capabilities[] = MapCapability::Display;
        }

        if (filled($this->credentials->serverKey)) {
            $capabilities[] = MapCapability::Geocoding;
            $capabilities[] = MapCapability::Routing;
        }

        return $capabilities;
    }

    public function frontendConfig(): array
    {
        return [
            'type' => 'google',
            'provider' => $this->key,
            'api_key' => $this->credentials->publicKey,
            'map_id' => $this->credentials->option('map_id'),
        ];
    }

    public function geocode(string $query, array $options = []): array
    {
        $params = ['address' => $query, 'key' => $this->credentials->serverKey];

        if (filled($country = $this->countryOption($options))) {
            $params['components'] = 'country:'.strtoupper($country);
        }

        $results = $this->geocodeRequest($params);
        $limit = (int) ($options['limit'] ?? 5);

        return collect($results)
            ->take($limit)
            ->map(fn (array $item): GeocodeResult => $this->toResult($item))
            ->values()
            ->all();
    }

    public function reverse(float $latitude, float $longitude): ?GeocodeResult
    {
        $results = $this->geocodeRequest([
            'latlng' => $latitude.','.$longitude,
            'key' => $this->credentials->serverKey,
        ]);

        return isset($results[0]) ? $this->toResult($results[0]) : null;
    }

    public function route(array $origin, array $destination): ?RouteResult
    {
        $response = $this->http()
            ->withHeaders([
                'X-Goog-Api-Key' => (string) $this->credentials->serverKey,
                'X-Goog-FieldMask' => 'routes.distanceMeters,routes.duration,routes.polyline.encodedPolyline',
            ])
            ->post(self::ROUTES_URL, [
                'origin' => ['location' => ['latLng' => ['latitude' => $origin[0], 'longitude' => $origin[1]]]],
                'destination' => ['location' => ['latLng' => ['latitude' => $destination[0], 'longitude' => $destination[1]]]],
                'travelMode' => 'DRIVE',
                'routingPreference' => 'TRAFFIC_UNAWARE',
            ])
            ->throw();

        $route = $response->json('routes.0');

        if (! is_array($route)) {
            return null;
        }

        return new RouteResult(
            distanceMeters: (int) ($route['distanceMeters'] ?? 0),
            durationSeconds: (int) rtrim((string) ($route['duration'] ?? '0'), 's'),
            points: EncodedPolyline::decode((string) ($route['polyline']['encodedPolyline'] ?? '')),
            provider: $this->key,
        );
    }

    /**
     * @param  array<string, mixed>  $params
     * @return list<array<string, mixed>>
     */
    private function geocodeRequest(array $params): array
    {
        /** @var Response $response */
        $response = $this->http()->get(self::GEOCODE_URL, $params)->throw();
        $status = (string) $response->json('status');

        if ($status === 'ZERO_RESULTS') {
            return [];
        }

        if ($status !== 'OK') {
            throw new RuntimeException('Google geocoding failed: '.($response->json('error_message') ?? $status));
        }

        return array_values((array) $response->json('results'));
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function toResult(array $item): GeocodeResult
    {
        $components = collect($item['address_components'] ?? []);
        $part = fn (string $type, string $field = 'long_name'): ?string => $components
            ->first(fn (array $component): bool => in_array($type, $component['types'] ?? [], true))[$field] ?? null;

        $street = trim(($part('street_number') ?? '').' '.($part('route') ?? ''));

        return new GeocodeResult(
            label: (string) ($item['formatted_address'] ?? ''),
            latitude: (float) $item['geometry']['location']['lat'],
            longitude: (float) $item['geometry']['location']['lng'],
            provider: $this->key,
            address: [
                'street' => $street !== '' ? $street : null,
                'suburb' => $part('sublocality') ?? $part('locality'),
                'city' => $part('locality'),
                'state' => $part('administrative_area_level_1'),
                'postcode' => $part('postal_code'),
                'country' => $part('country'),
                'country_code' => ($code = $part('country', 'short_name')) !== null ? strtolower($code) : null,
            ],
        );
    }
}
