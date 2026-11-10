<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Single active branch
    |--------------------------------------------------------------------------
    |
    | When true, a driver may have only one active branch assignment at a
    | time. Assigning another branch transfers them and keeps the previous
    | row as inactive history. When false, a driver may be active on
    | multiple branches at once.
    |
    */

    'single_active_branch' => env('DRIVER_SINGLE_ACTIVE_BRANCH', true),

    /*
    |--------------------------------------------------------------------------
    | Live location tracking
    |--------------------------------------------------------------------------
    |
    | Drivers share their position only while on duty (inside a shift window and
    | not on break) or while they still hold an active delivery. The last
    | position lives in the cache; only sampled breadcrumbs during deliveries are
    | written to driver_locations.
    |
    */

    'tracking' => [
        // When true, a driver who should be sharing location but has no fresh position is
        // blocked from the portal until they allow location. Readings are collected either way.
        'gate_enabled' => (bool) env('DRIVER_TRACKING_GATE', true),
        // Push positions over Reverb only while a live map is open (saves Reverb traffic).
        'broadcast_only_when_watched' => (bool) env('DRIVER_TRACKING_WATCH_ONLY', true),
        // Browser tracker pacing (seconds / metres). Sent to the app through shared props.
        'client' => [
            'heartbeat_seconds' => 45,
            'min_send_interval_seconds' => 10,
            'min_distance_meters' => 15,
            'recheck_seconds' => 120,
        ],
        // A position older than this counts as lost signal (seconds).
        'stale_after_seconds' => (int) env('DRIVER_TRACKING_STALE_AFTER', 120),
        // How long the last known position is kept in the cache (seconds).
        'last_position_ttl_seconds' => (int) env('DRIVER_TRACKING_POSITION_TTL', 300),
        // Ignore readings less accurate than this (metres). Laptops and desktops without GPS report
        // a few hundred metres from Wi-Fi, so the limit is generous; the live map shows the accuracy.
        'max_accuracy_meters' => (int) env('DRIVER_TRACKING_MAX_ACCURACY', 1500),
        // Between max_accuracy_meters and this limit a reading is kept as "approximate" (a computer
        // without GPS gives an area of ~25 km): it counts as sharing but is shown as rough on the map
        // and never recorded as a path. Beyond this limit it is dropped.
        'max_coarse_accuracy_meters' => (int) env('DRIVER_TRACKING_MAX_COARSE_ACCURACY', 100000),
        // Reject a reading that implies a faster trip than this (km/h).
        'max_speed_kmh' => (int) env('DRIVER_TRACKING_MAX_SPEED', 200),
        // Reject readings whose device timestamp is older than this (seconds).
        'max_reading_age_seconds' => 120,
        // Only broadcast after the driver has moved this far (metres)...
        'min_broadcast_distance_meters' => 10,
        // ...or when this long has passed since the last broadcast (heartbeat, seconds).
        'broadcast_heartbeat_seconds' => 60,
        // Breadcrumb sampling during an active delivery.
        'breadcrumb_min_distance_meters' => 50,
        'breadcrumb_heartbeat_seconds' => 300,
        'retention_days' => (int) env('DRIVER_TRACKING_RETENTION_DAYS', 30),
    ],

];
