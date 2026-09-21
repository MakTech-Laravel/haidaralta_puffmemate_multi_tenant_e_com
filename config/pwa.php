<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Product name
    |--------------------------------------------------------------------------
    |
    | One name in every environment — no staging/local suffix. Identity is
    | per origin, so agency-host installs do not follow the client domain.
    |
    */

    'name' => env('PWA_NAME', env('APP_NAME', 'PuffMeMate')),

    'short_name' => env('PWA_SHORT_NAME', env('APP_NAME', 'PuffMeMate')),

    'lang' => 'en-AU',

    'theme_color' => '#C4472F',

    'background_color' => '#FFFFFF',

    /*
    |--------------------------------------------------------------------------
    | Service worker kill-switch
    |--------------------------------------------------------------------------
    |
    | When false, /firebase-messaging-sw.js serves an unregistering worker
    | so a bad script can be pulled from clients without a store update.
    |
    */

    'sw_enabled' => (bool) env('PWA_SW_ENABLED', true),

    'id' => '/',

    'scope' => '/',

    'display' => 'standalone',

    'orientation' => 'any',

    'categories' => ['shopping'],

    'shortcuts' => [
        [
            'name' => 'Shop',
            'short_name' => 'Shop',
            'url' => '/shop',
        ],
        [
            'name' => 'Cart',
            'short_name' => 'Cart',
            'url' => '/cart',
        ],
        [
            'name' => 'Log in',
            'short_name' => 'Log in',
            'url' => '/login',
        ],
    ],

    'icons' => [
        [
            'src' => '/pwa/pwa-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/pwa/pwa-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any',
        ],
        [
            'src' => '/pwa/maskable-icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'maskable',
        ],
    ],

];
