<?php

/**
 * Normalize an origin URL: scheme + host [+ port], no path, no trailing slash.
 */
$normalizeOrigin = static function (?string $value): ?string {
    $value = trim((string) $value);

    if ($value === '') {
        return null;
    }

    $parts = parse_url($value);

    if ($parts === false || empty($parts['scheme']) || empty($parts['host'])) {
        return null;
    }

    $origin = strtolower($parts['scheme']).'://'.strtolower($parts['host']);

    if (! empty($parts['port'])) {
        $origin .= ':'.$parts['port'];
    }

    return $origin;
};

$extraOrigins = array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')));

$allowedOrigins = array_values(array_unique(array_filter(array_map(
    $normalizeOrigin,
    array_merge($extraOrigins, [
        env('FRONTEND_URL'),
        env('APP_URL'),
    ]),
))));

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | HTTP CORS uses FULL origin URLs (scheme + host [+ port]).
    | Do NOT confuse with REVERB_ALLOWED_ORIGINS on the Reverb server,
    | which expects bare hostnames only (no https://).
    |
    | supports_credentials must be true for Fortify session cookies.
    | Because credentials are true, allowed_origins must never be ['*'].
    |
    */

    'paths' => [
        'api/*',
        'broadcasting/auth',
        'broadcasting/*',
        'platform/realtime/*',
    ],

    'allowed_methods' => ['*'],

    'allowed_origins' => $allowedOrigins,

    'allowed_origins_patterns' => array_values(array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS_PATTERNS', '')))
    )),

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => true,

];
