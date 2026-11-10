<?php

use App\Services\Maps\MapSettingsService;
use Illuminate\Support\Facades\Http;

function nominatimSydney(): array
{
    return [[
        'display_name' => '1 George St, Sydney NSW 2000, Australia',
        'lat' => '-33.8688',
        'lon' => '151.2093',
        'address' => [
            'road' => 'George Street',
            'house_number' => '1',
            'city' => 'Sydney',
            'state' => 'New South Wales',
            'postcode' => '2000',
            'country_code' => 'au',
        ],
    ]];
}

test('address search returns places in the shape the address inputs expect', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimSydney())]);

    $this->getJson(route('maps.geocode', ['q' => '1 George St', 'country' => 'au']))
        ->assertOk()
        ->assertJsonPath('results.0.displayName', '1 George St, Sydney NSW 2000, Australia')
        ->assertJsonPath('results.0.latitude', '-33.8688')
        ->assertJsonPath('results.0.city', 'Sydney')
        ->assertJsonPath('results.0.postalCode', '2000');

    Http::assertSent(fn ($request) => $request['countrycodes'] === 'au');
});

test('a repeated search is served from the cache without another provider call', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimSydney())]);

    $this->getJson(route('maps.geocode', ['q' => '1 George St']))->assertOk();
    $this->getJson(route('maps.geocode', ['q' => '  1 george st ']))->assertOk();

    Http::assertSentCount(1);
});

test('short or oversized queries are rejected before reaching a provider', function () {
    Http::fake();

    $this->getJson(route('maps.geocode', ['q' => 'ab']))->assertUnprocessable();
    $this->getJson(route('maps.geocode', ['q' => str_repeat('a', 201)]))->assertUnprocessable();

    Http::assertNothingSent();
});

test('reverse geocoding enforces the requested country', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response(nominatimSydney()[0])]);

    $this->getJson(route('maps.reverse', ['lat' => -33.8688, 'lng' => 151.2093, 'country' => 'au']))
        ->assertOk()
        ->assertJsonPath('result.countryCode', 'au')
        ->assertJsonPath('result.city', 'Sydney');

    $this->getJson(route('maps.reverse', ['lat' => -33.8688, 'lng' => 151.2093, 'country' => 'nz']))
        ->assertOk()
        ->assertJsonPath('result', null);
});

test('the proxy answers 503 when every provider fails', function () {
    Http::fake(['*' => Http::response('down', 503)]);

    $this->getJson(route('maps.geocode', ['q' => 'George Street']))->assertStatus(503);
});

test('the browser config exposes the active renderer and never the server key', function () {
    app(MapSettingsService::class)->updateProvider('google', ['public_key' => 'browser-key', 'server_key' => 'server-secret']);
    app(MapSettingsService::class)->updateSelection([
        'display_provider' => 'google',
        'geocoding_provider' => 'osm',
        'routing_provider' => 'osm',
        'fallback_provider' => 'osm',
    ]);

    $response = $this->getJson(route('maps.config'))
        ->assertOk()
        ->assertJsonPath('display.type', 'google')
        ->assertJsonPath('display.api_key', 'browser-key')
        ->assertJsonPath('leaflet.type', 'leaflet')
        ->assertJsonPath('leaflet.key', 'osm');

    expect($response->getContent())->not->toContain('server-secret');
});

test('the map endpoints are open to guests', function () {
    $this->getJson(route('maps.config'))->assertOk()->assertJsonPath('display.key', 'osm');
});
