<?php

use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\TelescopeServiceProvider;

$providers = [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    FortifyServiceProvider::class,
];

/**
 * Read env early — Laravel's env() helper returns null when config is cached,
 * so bootstrap/providers.php must use $_ENV / getenv for this gate.
 */
$appEnv = $_ENV['APP_ENV'] ?? $_SERVER['APP_ENV'] ?? getenv('APP_ENV') ?: 'production';
$telescopeEnabled = filter_var(
    $_ENV['TELESCOPE_ENABLED']
        ?? $_SERVER['TELESCOPE_ENABLED']
        ?? (getenv('TELESCOPE_ENABLED') !== false ? getenv('TELESCOPE_ENABLED') : true),
    FILTER_VALIDATE_BOOLEAN,
);

if (class_exists(TelescopeServiceProvider::class)
    && in_array($appEnv, ['local', 'staging'], true)
    && $telescopeEnabled
) {
    $providers[] = TelescopeServiceProvider::class;
}

return $providers;
