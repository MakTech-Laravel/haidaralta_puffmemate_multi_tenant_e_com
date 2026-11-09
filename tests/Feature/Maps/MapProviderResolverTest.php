<?php

use App\Enums\MapCapability;
use App\Models\MapProvider;
use App\Models\MapSetting;
use App\Services\Maps\Exceptions\MapProviderUnavailableException;
use App\Services\Maps\MapProviderResolver;
use App\Services\Maps\MapSettingsService;
use App\Services\Maps\MapUsageRecorder;
use Illuminate\Support\Facades\DB;

function mapSettings(): MapSettingsService
{
    return app(MapSettingsService::class);
}

function selectProviders(string $display, string $geocoding, string $routing, string $fallback = 'osm'): void
{
    mapSettings()->updateSelection([
        'display_provider' => $display,
        'geocoding_provider' => $geocoding,
        'routing_provider' => $routing,
        'fallback_provider' => $fallback,
    ]);
}

test('a row per registered provider and the default selection are created on first use', function () {
    expect(MapProvider::query()->count())->toBe(0);

    $settings = mapSettings()->settings();

    expect(MapProvider::query()->pluck('key')->sort()->values()->all())->toBe(['google', 'mapbox', 'osm'])
        ->and($settings->display_provider)->toBe('osm')
        ->and(MapSetting::query()->count())->toBe(1);
});

test('syncing registers a provider newly added to the code registry', function () {
    mapSettings()->sync();
    MapProvider::query()->where('key', 'mapbox')->delete();

    mapSettings()->sync();

    expect(MapProvider::query()->where('key', 'mapbox')->exists())->toBeTrue();
});

test('a key stored in the database wins over the env fallback and clearing it restores the fallback', function () {
    config(['maps.providers.google.env.public_key' => 'env-key']);

    expect(mapSettings()->credentials('google')->publicKey)->toBe('env-key');

    mapSettings()->updateProvider('google', ['public_key' => 'db-key']);

    expect(mapSettings()->credentials('google')->publicKey)->toBe('db-key');

    mapSettings()->updateProvider('google', ['clear_public_key' => true]);

    expect(mapSettings()->credentials('google')->publicKey)->toBe('env-key');
});

test('a blank key submission keeps the stored key', function () {
    mapSettings()->updateProvider('google', ['server_key' => 'kept']);
    mapSettings()->updateProvider('google', ['server_key' => '', 'is_enabled' => true]);

    expect(mapSettings()->credentials('google')->serverKey)->toBe('kept');
});

test('keys are encrypted at rest and cached as ciphertext', function () {
    mapSettings()->updateProvider('google', ['server_key' => 'plain-secret']);
    mapSettings()->credentials('google');

    $raw = DB::table('map_providers')->where('key', 'google')->value('server_key');
    $cached = collect(cache()->get(MapSettingsService::PROVIDERS_CACHE_KEY))->firstWhere('key', 'google');

    expect($raw)->not->toBe('plain-secret')->not->toContain('plain-secret')
        ->and($cached['server_key'])->not->toContain('plain-secret');
});

test('updating a provider invalidates the cache immediately', function () {
    mapSettings()->updateProvider('mapbox', ['public_key' => 'first']);
    expect(mapSettings()->credentials('mapbox')->publicKey)->toBe('first');

    mapSettings()->updateProvider('mapbox', ['public_key' => 'second']);

    expect(mapSettings()->credentials('mapbox')->publicKey)->toBe('second');
});

test('unknown providers cannot be saved or selected', function () {
    mapSettings()->updateProvider('leaflet-cloud', ['public_key' => 'x']);
})->throws(InvalidArgumentException::class);

test('an unknown provider cannot be selected', function () {
    selectProviders('osm', 'osm', 'osm', 'nope');
})->throws(InvalidArgumentException::class);

test('the active display provider is used when it is configured', function () {
    mapSettings()->updateProvider('google', ['public_key' => 'pub']);
    selectProviders('google', 'osm', 'osm');

    expect(app(MapProviderResolver::class)->resolve(MapCapability::Display)->key())->toBe('google');
});

test('an unconfigured selection falls back to the fallback provider', function () {
    selectProviders('google', 'mapbox', 'google', 'osm');

    $resolver = app(MapProviderResolver::class);

    expect($resolver->resolve(MapCapability::Display)->key())->toBe('osm')
        ->and($resolver->resolve(MapCapability::Geocoding)->key())->toBe('osm');
});

test('routing falls through to the default provider only when it supports routing', function () {
    selectProviders('osm', 'osm', 'google', 'osm');

    app(MapProviderResolver::class)->resolve(MapCapability::Routing);
})->throws(MapProviderUnavailableException::class);

test('a disabled provider is skipped even when it has keys', function () {
    mapSettings()->updateProvider('google', ['public_key' => 'pub', 'is_enabled' => false]);
    selectProviders('google', 'osm', 'osm');

    expect(app(MapProviderResolver::class)->resolve(MapCapability::Display)->key())->toBe('osm');
});

test('a provider over its daily limit is skipped for billable capabilities only', function () {
    mapSettings()->updateProvider('google', ['public_key' => 'pub', 'server_key' => 'srv', 'daily_limit' => 2]);
    selectProviders('google', 'google', 'google');

    $resolver = app(MapProviderResolver::class);
    expect($resolver->resolve(MapCapability::Geocoding)->key())->toBe('google');

    $usage = app(MapUsageRecorder::class);
    $usage->record('google', MapCapability::Geocoding);
    $usage->record('google', MapCapability::Geocoding);

    expect($usage->dailyTotal('google'))->toBe(2)
        ->and($resolver->resolve(MapCapability::Geocoding)->key())->toBe('osm')
        ->and($resolver->resolve(MapCapability::Display)->key())->toBe('google');
});

test('the monthly limit also triggers the fallback', function () {
    mapSettings()->updateProvider('mapbox', ['public_key' => 'tok', 'monthly_limit' => 1]);
    selectProviders('osm', 'mapbox', 'osm');

    app(MapUsageRecorder::class)->record('mapbox', MapCapability::Geocoding);

    expect(app(MapProviderResolver::class)->resolve(MapCapability::Geocoding)->key())->toBe('osm');
});

test('usage is counted per provider capability and day in one row', function () {
    $usage = app(MapUsageRecorder::class);

    $usage->record('google', MapCapability::Geocoding);
    $usage->record('google', MapCapability::Geocoding);
    $usage->record('google', MapCapability::Routing);

    expect(DB::table('map_provider_usages')->count())->toBe(2)
        ->and($usage->monthlyTotal('google'))->toBe(3);
});
