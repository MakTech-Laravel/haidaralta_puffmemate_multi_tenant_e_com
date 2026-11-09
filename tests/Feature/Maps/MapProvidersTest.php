<?php

use App\Enums\MapCapability;
use App\Services\Maps\Data\MapCredentials;
use App\Services\Maps\Providers\GoogleMapsProvider;
use App\Services\Maps\Providers\MapboxProvider;
use App\Services\Maps\Providers\OpenStreetMapProvider;
use Illuminate\Support\Facades\Http;

function googleProvider(?string $publicKey = 'pub', ?string $serverKey = 'srv'): GoogleMapsProvider
{
    return new GoogleMapsProvider('google', 'Google Maps', new MapCredentials($publicKey, $serverKey, ['map_id' => 'MAP1']));
}

test('capabilities follow the credentials that are present', function () {
    expect(googleProvider('pub', null)->capabilities())->toBe([MapCapability::Display])
        ->and(googleProvider(null, 'srv')->capabilities())->toBe([MapCapability::Geocoding, MapCapability::Routing])
        ->and(googleProvider(null, null)->isConfigured())->toBeFalse();

    $mapbox = new MapboxProvider('mapbox', 'Mapbox', new MapCredentials('tok', null));
    expect($mapbox->capabilities())->toBe([MapCapability::Display, MapCapability::Geocoding, MapCapability::Routing]);

    $osm = new OpenStreetMapProvider('osm', 'OpenStreetMap', new MapCredentials);
    expect($osm->capabilities())->toBe([MapCapability::Display, MapCapability::Geocoding]);

    $osmWithRouting = new OpenStreetMapProvider('osm', 'OpenStreetMap', new MapCredentials(options: ['osrm_url' => 'https://osrm.test']));
    expect($osmWithRouting->supports(MapCapability::Routing))->toBeTrue();
});

test('the google frontend config exposes only the browser key', function () {
    $config = googleProvider('pub', 'super-secret')->frontendConfig();

    expect($config)->toMatchArray(['type' => 'google', 'api_key' => 'pub', 'map_id' => 'MAP1'])
        ->and(json_encode($config))->not->toContain('super-secret');
});

test('the mapbox tile url uses the public token and the configured style', function () {
    $provider = new MapboxProvider('mapbox', 'Mapbox', new MapCredentials('pk.abc', 'sk.secret', ['style' => 'mapbox/dark-v11']));

    $config = $provider->frontendConfig();

    expect($config['tile_url'])->toContain('styles/v1/mapbox/dark-v11')->toContain('access_token=pk.abc')
        ->and(json_encode($config))->not->toContain('sk.secret');
});

test('google geocoding maps the response and scopes it to the default country', function () {
    Http::fake([
        'maps.googleapis.com/*' => Http::response([
            'status' => 'OK',
            'results' => [[
                'formatted_address' => '1 George St, Sydney NSW 2000, Australia',
                'geometry' => ['location' => ['lat' => -33.8688, 'lng' => 151.2093]],
                'address_components' => [
                    ['long_name' => '1', 'types' => ['street_number']],
                    ['long_name' => 'George Street', 'types' => ['route']],
                    ['long_name' => 'Sydney', 'types' => ['locality']],
                    ['long_name' => 'New South Wales', 'types' => ['administrative_area_level_1']],
                    ['long_name' => '2000', 'types' => ['postal_code']],
                    ['long_name' => 'Australia', 'types' => ['country']],
                ],
            ]],
        ]),
    ]);

    $results = googleProvider()->geocode('1 George St Sydney');

    expect($results)->toHaveCount(1)
        ->and($results[0]->latitude)->toBe(-33.8688)
        ->and($results[0]->address['street'])->toBe('1 George Street')
        ->and($results[0]->address['postcode'])->toBe('2000');

    Http::assertSent(fn ($request) => $request['components'] === 'country:AU' && $request['key'] === 'srv');
});

test('google geocoding treats zero results as empty and a denied key as an error', function () {
    Http::fake(['maps.googleapis.com/*' => Http::sequence()
        ->push(['status' => 'ZERO_RESULTS', 'results' => []])
        ->push(['status' => 'REQUEST_DENIED', 'error_message' => 'The provided API key is invalid.', 'results' => []])]);

    expect(googleProvider()->geocode('nowhere'))->toBe([]);

    googleProvider()->geocode('anything');
})->throws(RuntimeException::class, 'The provided API key is invalid.');

test('google routing decodes the polyline and parses the duration', function () {
    Http::fake([
        'routes.googleapis.com/*' => Http::response([
            'routes' => [[
                'distanceMeters' => 4200,
                'duration' => '780s',
                'polyline' => ['encodedPolyline' => '_p~iF~ps|U_ulLnnqC'],
            ]],
        ]),
    ]);

    $route = googleProvider()->route([-33.86, 151.2], [-33.9, 151.25]);

    expect($route->distanceMeters)->toBe(4200)
        ->and($route->durationSeconds)->toBe(780)
        ->and($route->points)->toHaveCount(2);

    Http::assertSent(fn ($request) => $request->hasHeader('X-Goog-Api-Key', 'srv') && $request->hasHeader('X-Goog-FieldMask'));
});

test('mapbox directions flips geojson coordinates into latitude longitude', function () {
    Http::fake([
        'api.mapbox.com/directions/*' => Http::response([
            'routes' => [[
                'distance' => 1500.4,
                'duration' => 300.6,
                'geometry' => ['coordinates' => [[151.2, -33.86], [151.21, -33.87]]],
            ]],
        ]),
    ]);

    $route = (new MapboxProvider('mapbox', 'Mapbox', new MapCredentials('tok')))->route([-33.86, 151.2], [-33.87, 151.21]);

    expect($route->distanceMeters)->toBe(1500)
        ->and($route->durationSeconds)->toBe(301)
        ->and($route->points[0])->toBe([-33.86, 151.2]);
});

test('nominatim results are mapped and the connection test reports success', function () {
    Http::fake([
        'nominatim.openstreetmap.org/*' => Http::response([[
            'display_name' => 'Sydney, New South Wales, Australia',
            'lat' => '-33.8698',
            'lon' => '151.2083',
            'address' => ['city' => 'Sydney', 'state' => 'New South Wales', 'country' => 'Australia'],
        ]]),
    ]);

    $osm = new OpenStreetMapProvider('osm', 'OpenStreetMap', new MapCredentials);

    expect($osm->geocode('Sydney')[0]->address['city'])->toBe('Sydney')
        ->and($osm->testConnection()['ok'])->toBeTrue();
});

test('the connection test fails cleanly when no key is configured', function () {
    expect(googleProvider(null, null)->testConnection())->toBe([
        'ok' => false,
        'message' => 'No API key is configured for this provider.',
    ]);
});
