<?php

namespace App\Http\Controllers;

use App\Services\Maps\Data\GeocodeResult;
use App\Services\Maps\Exceptions\MapProviderUnavailableException;
use App\Services\Maps\MapManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Browser-facing map endpoints. Address search goes through the server so the provider key
 * never reaches the browser, answers are cached, and every paid call is counted.
 */
class MapProxyController extends Controller
{
    public function __construct(private readonly MapManager $maps) {}

    public function config(): JsonResponse
    {
        try {
            return response()->json([
                'display' => $this->maps->displayConfig(),
                'leaflet' => $this->maps->leafletDisplayConfig(),
            ])->header('Cache-Control', 'private, max-age=300');
        } catch (MapProviderUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }
    }

    public function geocode(Request $request): JsonResponse
    {
        $data = $request->validate([
            'q' => ['required', 'string', 'min:3', 'max:200'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        try {
            $results = $this->maps->geocode($data['q'], array_filter([
                'country' => $data['country'] ?? null,
                'limit' => 5,
            ]));
        } catch (MapProviderUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        return response()->json([
            'results' => array_map(fn (GeocodeResult $result): array => $this->toPlace($result), $results),
        ]);
    }

    public function reverse(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lat' => ['required', 'numeric', 'between:-90,90'],
            'lng' => ['required', 'numeric', 'between:-180,180'],
            'country' => ['nullable', 'string', 'size:2'],
        ]);

        try {
            $result = $this->maps->reverse((float) $data['lat'], (float) $data['lng']);
        } catch (MapProviderUnavailableException $exception) {
            return response()->json(['message' => $exception->getMessage()], 503);
        }

        $country = isset($data['country']) ? strtolower($data['country']) : null;

        if ($result !== null && $country !== null && ($result->address['country_code'] ?? null) !== $country) {
            $result = null;
        }

        return response()->json(['result' => $result === null ? null : $this->toPlace($result)]);
    }

    /**
     * Same field names the address inputs already use for Nominatim places.
     *
     * @return array{displayName: string, latitude: string, longitude: string, city?: string, state?: string, postalCode?: string, countryCode?: string}
     */
    private function toPlace(GeocodeResult $result): array
    {
        $address = $result->address;

        return array_filter([
            'displayName' => $result->label,
            'latitude' => (string) $result->latitude,
            'longitude' => (string) $result->longitude,
            'city' => $address['city'] ?? $address['suburb'] ?? null,
            'state' => $address['state'] ?? null,
            'postalCode' => $address['postcode'] ?? null,
            'countryCode' => $address['country_code'] ?? null,
        ], fn (?string $value): bool => $value !== null && $value !== '');
    }
}
