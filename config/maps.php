<?php

use App\Services\Maps\Providers\GoogleMapsProvider;
use App\Services\Maps\Providers\MapboxProvider;
use App\Services\Maps\Providers\OpenStreetMapProvider;

return [

    /*
    |--------------------------------------------------------------------------
    | Provider registry
    |--------------------------------------------------------------------------
    |
    | The list of selectable providers is defined in code. The platform dashboard
    | can only pick from this list, set keys and limits; it cannot add providers.
    | To add a provider, write a class extending AbstractMapProvider and register
    | it here. Keys below are the deployment fallback used when the dashboard has
    | not stored a key for that provider.
    |
    */
    'providers' => [
        'osm' => [
            'class' => OpenStreetMapProvider::class,
            'label' => 'OpenStreetMap',
            'description' => 'Free, no key needed. Public OSM servers are for light use; point the URLs below at a commercial tile/geocoder host for heavy traffic.',
            'env' => [
                'public_key' => null,
                'server_key' => null,
            ],
            'fields' => [],
            'options' => [
                'tile_url' => env('OSM_TILE_URL', 'https://tile.openstreetmap.org/{z}/{x}/{y}.png'),
                'nominatim_url' => env('NOMINATIM_URL', 'https://nominatim.openstreetmap.org'),
                'osrm_url' => env('OSRM_URL'),
            ],
            'option_labels' => [
                'tile_url' => 'Tile URL',
                'nominatim_url' => 'Nominatim (address search) URL',
                'osrm_url' => 'OSRM routing URL (optional, enables routing)',
            ],
        ],
        'mapbox' => [
            'class' => MapboxProvider::class,
            'label' => 'Mapbox',
            'description' => 'Map tiles, address search and routing. One public token is enough; add a secret token to keep server calls off the public token.',
            'env' => [
                'public_key' => env('MAPBOX_PUBLIC_TOKEN'),
                'server_key' => env('MAPBOX_SECRET_TOKEN'),
            ],
            'fields' => [
                'public_key' => [
                    'label' => 'Public access token',
                    'help' => 'Starts with pk. Shown in the browser: restrict it by URL in the Mapbox dashboard.',
                ],
                'server_key' => [
                    'label' => 'Secret token (optional)',
                    'help' => 'Starts with sk. Used only by the server for address search and routing.',
                ],
            ],
            'options' => [
                'style' => env('MAPBOX_STYLE', 'mapbox/streets-v12'),
            ],
            'option_labels' => [
                'style' => 'Map style (owner/style-id)',
            ],
        ],
        'google' => [
            'class' => GoogleMapsProvider::class,
            'label' => 'Google Maps',
            'description' => 'Most detailed data, billed per request. Two keys are needed: one for the map in the browser, one for server-side address search and routing.',
            'env' => [
                'public_key' => env('GOOGLE_MAPS_PUBLIC_KEY'),
                'server_key' => env('GOOGLE_MAPS_SERVER_KEY'),
            ],
            'fields' => [
                'public_key' => [
                    'label' => 'Browser key (Maps JavaScript API)',
                    'help' => 'Shown in the browser: restrict it by HTTP referrer in Google Cloud.',
                ],
                'server_key' => [
                    'label' => 'Server key (Geocoding + Routes API)',
                    'help' => 'Never sent to the browser: restrict it by server IP in Google Cloud.',
                ],
            ],
            'options' => [
                'map_id' => env('GOOGLE_MAPS_MAP_ID'),
            ],
            'option_labels' => [
                'map_id' => 'Map ID (optional)',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Initial selection
    |--------------------------------------------------------------------------
    |
    | Used once to seed map_settings. After that the dashboard owns the choice.
    | "default_provider" is the last resort when the selected and fallback
    | providers are unusable. OpenStreetMap needs no key.
    |
    */
    'defaults' => [
        'display' => env('MAPS_DISPLAY_PROVIDER', 'osm'),
        'geocoding' => env('MAPS_GEOCODING_PROVIDER', 'osm'),
        'routing' => env('MAPS_ROUTING_PROVIDER', 'osm'),
        'fallback' => env('MAPS_FALLBACK_PROVIDER', 'osm'),
    ],

    'default_provider' => 'osm',

    'default_country' => env('MAPS_DEFAULT_COUNTRY', 'au'),

    'http_timeout' => (int) env('MAPS_HTTP_TIMEOUT', 5),

    /*
    |--------------------------------------------------------------------------
    | Response caching (cost control)
    |--------------------------------------------------------------------------
    |
    | Geocode and route answers are cached so repeated lookups never reach a paid
    | API. Google's terms limit how long coordinates may be cached, so keep the
    | geocode TTL at 30 days or less.
    |
    */
    'cache' => [
        'geocode_ttl_days' => (int) env('MAPS_GEOCODE_TTL_DAYS', 30),
        'route_ttl_minutes' => (int) env('MAPS_ROUTE_TTL_MINUTES', 10),
    ],

];
