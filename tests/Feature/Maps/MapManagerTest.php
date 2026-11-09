<?php

use App\Services\Maps\Exceptions\MapProviderUnavailableException;
use App\Services\Maps\MapManager;
use App\Services\Maps\MapSettingsService;
use App\Services\Maps\MapUsageRecorder;
use Illuminate\Support\Facades\Http;

function nominatimHit(): array
{
    return [[
        'display_name' => '1 George St, Sydney NSW, Australia',
        'lat' => '-33.8688',
        'lon' => '151.2093',
        'address' => ['road' => 'George Street', 'house_number' => '1', 'city' => 'Sydney'],
    ]];
}

test('a repeated address search is answered from cache without another provider request', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimHit())]);

    $manager = app(MapManager::class);

    $first = $manager->geocode('1 George St  Sydney');
    $second = $manager->geocode('  1 george st sydney ');

    expect($second[0]->label)->toBe($first[0]->label);
    Http::assertSentCount(1);
    expect(app(MapUsageRecorder::class)->dailyTotal('osm'))->toBe(1);
});

test('a blank query never reaches a provider', function () {
    Http::fake();

    expect(app(MapManager::class)->geocode('   '))->toBe([]);

    Http::assertNothingSent();
});

test('a failing provider is skipped and the fallback answers', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv']);
    app(MapSettingsService::class)->updateSelection([
        'display_provider' => 'osm',
        'geocoding_provider' => 'google',
        'routing_provider' => 'osm',
        'fallback_provider' => 'osm',
    ]);

    Http::fake([
        'maps.googleapis.com/*' => Http::response('boom', 500),
        'nominatim.openstreetmap.org/*' => Http::response(nominatimHit()),
    ]);

    $results = app(MapManager::class)->geocode('George St');

    expect($results[0]->provider)->toBe('osm');
    $usage = app(MapUsageRecorder::class);
    expect($usage->dailyTotal('osm'))->toBe(1)->and($usage->dailyTotal('google'))->toBe(0);
});

test('when every provider fails the caller gets an unavailable exception', function () {
    Http::fake(['*' => Http::response('down', 503)]);

    app(MapManager::class)->geocode('George St');
})->throws(MapProviderUnavailableException::class);

test('reverse geocoding is cached by rounded coordinates', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimHit()[0])]);

    $manager = app(MapManager::class);
    $manager->reverse(-33.868801, 151.209301);
    $manager->reverse(-33.868802, 151.209299);

    Http::assertSentCount(1);
});

test('routes are cached by rounded endpoints and use the routing provider', function () {
    app(MapSettingsService::class)->updateProvider('mapbox', ['public_key' => 'tok']);
    app(MapSettingsService::class)->updateSelection([
        'display_provider' => 'osm',
        'geocoding_provider' => 'osm',
        'routing_provider' => 'mapbox',
        'fallback_provider' => 'osm',
    ]);

    Http::fake([
        'api.mapbox.com/directions/*' => Http::response([
            'routes' => [[
                'distance' => 900,
                'duration' => 120,
                'geometry' => ['coordinates' => [[151.2, -33.86], [151.21, -33.87]]],
            ]],
        ]),
    ]);

    $manager = app(MapManager::class);
    $first = $manager->route([-33.86, 151.2], [-33.87, 151.21]);
    $second = $manager->route([-33.86001, 151.20001], [-33.87001, 151.21001]);

    expect($first->provider)->toBe('mapbox')
        ->and($second->distanceMeters)->toBe(900);
    Http::assertSentCount(1);
    expect(app(MapUsageRecorder::class)->dailyTotal('mapbox'))->toBe(1);
});

test('the display config never leaks the server key', function () {
    app(MapSettingsService::class)->updateProvider('google', ['public_key' => 'pub-key', 'server_key' => 'server-secret']);
    app(MapSettingsService::class)->updateSelection([
        'display_provider' => 'google',
        'geocoding_provider' => 'osm',
        'routing_provider' => 'osm',
        'fallback_provider' => 'osm',
    ]);

    $config = app(MapManager::class)->displayConfig();

    expect($config)->toMatchArray(['key' => 'google', 'type' => 'google', 'api_key' => 'pub-key'])
        ->and(json_encode($config))->not->toContain('server-secret');
});

test('after the daily limit the manager stops calling the paid provider', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'srv', 'daily_limit' => 1]);
    app(MapSettingsService::class)->updateSelection([
        'display_provider' => 'osm',
        'geocoding_provider' => 'google',
        'routing_provider' => 'osm',
        'fallback_provider' => 'osm',
    ]);

    Http::fake([
        'maps.googleapis.com/*' => Http::response(['status' => 'OK', 'results' => [[
            'formatted_address' => 'Sydney NSW',
            'geometry' => ['location' => ['lat' => -33.87, 'lng' => 151.2]],
            'address_components' => [],
        ]]]),
        'nominatim.openstreetmap.org/*' => Http::response(nominatimHit()),
    ]);

    $manager = app(MapManager::class);
    $first = $manager->geocode('first place');
    $second = $manager->geocode('second place');

    expect($first[0]->provider)->toBe('google')
        ->and($second[0]->provider)->toBe('osm')
        ->and(app(MapUsageRecorder::class)->dailyTotal('google'))->toBe(1);
});
