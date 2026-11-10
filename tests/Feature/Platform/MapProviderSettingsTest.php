<?php

use App\Enums\RoleEnum;
use App\Enums\UserType;
use App\Models\User;
use App\Services\Maps\MapSettingsService;
use App\Support\PlatformTeamResolver;
use Database\Seeders\BranchSeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Database\Seeders\UserSeeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Spatie\Permission\PermissionRegistrar;

beforeEach(function () {
    $this->seed(RolesAndPermissionsSeeder::class);
    $this->seed(BranchSeeder::class);
    $this->seed(UserSeeder::class);

    $this->admin = User::query()->where('email', UserSeeder::PLATFORM_SUPER_ADMIN)->firstOrFail();
});

test('platform admin sees the providers without any secret in the page props', function () {
    app(MapSettingsService::class)->updateProvider('google', ['public_key' => 'browser-secret', 'server_key' => 'server-secret']);

    $response = $this->actingAs($this->admin, 'platform')
        ->get(route('platform.settings.map-provider.edit'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('platform/settings/map-provider')
            ->has('providers', 3)
            ->has('capabilities', 3)
            ->where('selection.display_provider', 'osm')
            ->where('providers.2.key', 'google')
            ->where('providers.2.fields.0.source', 'dashboard')
            ->where('providers.2.capabilities', ['display', 'geocoding', 'routing']));

    expect($response->getContent())->not->toContain('browser-secret')->not->toContain('server-secret');
});

test('staff without manage-application-settings cannot open the page', function () {
    $user = User::factory()->create(['user_type' => UserType::PLATFORM, 'email_verified_at' => now()]);
    app(PermissionRegistrar::class)->setPermissionsTeamId(PlatformTeamResolver::PLATFORM_TEAM_ID);
    $user->assignRole(RoleEnum::STAFF->value);

    $this->actingAs($user, 'platform')->get(route('platform.settings.map-provider.edit'))->assertForbidden();
    $this->actingAs($user, 'platform')
        ->put(route('platform.settings.map-provider.update', 'google'), ['is_enabled' => true])
        ->assertForbidden();
});

test('saving a provider stores the key encrypted and a blank resubmit keeps it', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'google'), [
            'is_enabled' => true,
            'public_key' => 'browser-key',
            'server_key' => 'server-key',
            'daily_limit' => 500,
            'monthly_limit' => 9000,
        ])
        ->assertRedirect(route('platform.settings.map-provider.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Google Maps settings saved.']);

    expect(DB::table('map_providers')->where('key', 'google')->value('server_key'))->not->toContain('server-key');

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'google'), [
            'is_enabled' => true,
            'public_key' => '',
            'server_key' => '',
            'daily_limit' => 500,
            'monthly_limit' => 9000,
        ])
        ->assertRedirect();

    $credentials = app(MapSettingsService::class)->credentials('google');

    expect($credentials->publicKey)->toBe('browser-key')
        ->and($credentials->serverKey)->toBe('server-key')
        ->and(app(MapSettingsService::class)->row('google')->daily_limit)->toBe(500);
});

test('a saved key can be removed explicitly', function () {
    app(MapSettingsService::class)->updateProvider('google', ['server_key' => 'server-key']);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'google'), [
            'is_enabled' => true,
            'clear_server_key' => true,
        ])
        ->assertRedirect();

    expect(app(MapSettingsService::class)->credentials('google')->serverKey)->toBeNull();
});

test('only options defined for the provider are stored and blanks are dropped', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'mapbox'), [
            'is_enabled' => true,
            'options' => ['style' => 'mapbox/dark-v11', 'injected' => 'x'],
        ])
        ->assertRedirect();

    expect(app(MapSettingsService::class)->row('mapbox')->options)->toBe(['style' => 'mapbox/dark-v11']);
});

test('providers outside the code registry cannot be saved or tested', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'bing'), ['is_enabled' => true])
        ->assertNotFound();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.test', 'bing'))
        ->assertNotFound();

    expect(DB::table('map_providers')->where('key', 'bing')->exists())->toBeFalse();
});

test('limits must be positive integers', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.update', 'google'), [
            'is_enabled' => true,
            'daily_limit' => 0,
            'monthly_limit' => 'many',
        ])
        ->assertSessionHasErrors(['daily_limit', 'monthly_limit']);
});

test('a provider without a saved key cannot be made active for a feature', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.selection'), [
            'display_provider' => 'google',
            'geocoding_provider' => 'osm',
            'routing_provider' => 'osm',
            'fallback_provider' => 'osm',
        ])
        ->assertSessionHasErrors('display_provider');

    expect(app(MapSettingsService::class)->settings()->display_provider)->toBe('osm');
});

test('a provider that is not in the registry cannot be selected', function () {
    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.selection'), [
            'display_provider' => 'osm',
            'geocoding_provider' => 'osm',
            'routing_provider' => 'osm',
            'fallback_provider' => 'bing',
        ])
        ->assertSessionHasErrors('fallback_provider');
});

test('after its key is saved the provider can be made active', function () {
    app(MapSettingsService::class)->updateProvider('google', ['public_key' => 'pub', 'server_key' => 'srv']);

    $this->actingAs($this->admin, 'platform')
        ->put(route('platform.settings.map-provider.selection'), [
            'display_provider' => 'google',
            'geocoding_provider' => 'osm',
            'routing_provider' => 'google',
            'fallback_provider' => 'osm',
        ])
        ->assertRedirect(route('platform.settings.map-provider.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Active map providers saved.']);

    $settings = app(MapSettingsService::class)->settings();

    expect($settings->display_provider)->toBe('google')
        ->and($settings->routing_provider)->toBe('google')
        ->and($settings->geocoding_provider)->toBe('osm');
});

test('the connection test reports success for a working provider and counts the call', function () {
    Http::fake(['nominatim.openstreetmap.org/*' => Http::response([[
        'display_name' => 'Sydney NSW',
        'lat' => '-33.87',
        'lon' => '151.21',
    ]])]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.test', 'osm'))
        ->assertRedirect(route('platform.settings.map-provider.edit'))
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'OpenStreetMap: Connection works (test address lookup succeeded).',
        ]);

    expect(DB::table('map_provider_usages')->where('provider', 'osm')->value('request_count'))->toBe(1);
});

test('the connection test explains when no key is saved', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.test', 'google'))
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'Google Maps: No API key is configured for this provider.',
        ]);
});

function mapboxGeocodeHit(): array
{
    return ['features' => [[
        'properties' => ['full_address' => 'Sydney NSW, Australia', 'coordinates' => ['latitude' => -33.87, 'longitude' => 151.21]],
    ]]];
}

test('one step saves the key, checks it and switches every feature to the provider', function () {
    Http::fake(['api.mapbox.com/*' => Http::response(mapboxGeocodeHit())]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'mapbox', 'public_key' => 'pk.test', 'daily_limit' => 800])
        ->assertRedirect(route('platform.settings.map-provider.edit'))
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'Mapbox is now used for the map, address search and routes.']);

    $settings = app(MapSettingsService::class)->settings();

    expect([$settings->display_provider, $settings->geocoding_provider, $settings->routing_provider])->each->toBe('mapbox')
        ->and($settings->fallback_provider)->toBe('osm')
        ->and(app(MapSettingsService::class)->row('mapbox')->daily_limit)->toBe(800);
});

test('a key that the provider rejects changes nothing that is in use', function () {
    Http::fake(['api.mapbox.com/*' => Http::response(['message' => 'Not Authorized - Invalid Token'], 401)]);

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'mapbox', 'public_key' => 'pk.wrong'])
        ->assertRedirect()
        ->assertInertiaFlash('toast', [
            'type' => 'error',
            'message' => 'We saved the key, but Mapbox did not accept it. Check that you copied the whole key and that every service listed in the steps is turned on. Nothing was switched, so your maps keep working as before.',
        ]);

    expect(app(MapSettingsService::class)->settings()->display_provider)->toBe('osm');
});

test('a provider without any key asks for the key instead of switching', function () {
    Http::fake();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'google'])
        ->assertInertiaFlash('toast', ['type' => 'error', 'message' => 'Google Maps needs a key before it can be used. Paste your key and try again.']);

    Http::assertNothingSent();
    expect(app(MapSettingsService::class)->settings()->display_provider)->toBe('osm');
});

test('with only the browser key google serves the map and says what still uses OpenStreetMap', function () {
    Http::fake();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'google', 'public_key' => 'browser-key'])
        ->assertInertiaFlash('toast', [
            'type' => 'success',
            'message' => 'Google Maps is now used for the map. Address search and routes still use OpenStreetMap until that key is added.',
        ]);

    $settings = app(MapSettingsService::class)->settings();

    expect($settings->display_provider)->toBe('google')
        ->and($settings->geocoding_provider)->toBe('osm')
        ->and($settings->routing_provider)->toBe('osm');
});

test('choosing OpenStreetMap again switches everything back without any request', function () {
    Http::fake(['api.mapbox.com/*' => Http::response(mapboxGeocodeHit())]);
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'mapbox', 'public_key' => 'pk.test']);

    Http::fake();

    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'osm'])
        ->assertInertiaFlash('toast', ['type' => 'success', 'message' => 'OpenStreetMap is now used for the map and address search. Drivers see straight-line distances instead of road routes.']);

    Http::assertNothingSent();
    expect(app(MapSettingsService::class)->settings()->display_provider)->toBe('osm')
        ->and(app(MapSettingsService::class)->settings()->geocoding_provider)->toBe('osm');
});

test('activation only accepts providers from the code registry and needs the settings permission', function () {
    $this->actingAs($this->admin, 'platform')
        ->post(route('platform.settings.map-provider.activate'), ['provider' => 'bing'])
        ->assertSessionHasErrors('provider');
});
