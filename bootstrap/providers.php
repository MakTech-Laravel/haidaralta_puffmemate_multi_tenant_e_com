<?php
 
use App\Providers\AppServiceProvider;
use App\Providers\AuthServiceProvider;
use App\Providers\FortifyServiceProvider;
 
$providers = [
    AppServiceProvider::class,
    AuthServiceProvider::class,
    FortifyServiceProvider::class,
];
 
/**
* Read environment directly because this file executes during bootstrap.
*/
$appEnv = $_ENV['APP_ENV']
    ?? $_SERVER['APP_ENV']
    ?? (getenv('APP_ENV') !== false ? getenv('APP_ENV') : 'production');
 
$telescopeEnabled = filter_var(
    $_ENV['TELESCOPE_ENABLED']
        ?? $_SERVER['TELESCOPE_ENABLED']
        ?? (getenv('TELESCOPE_ENABLED') !== false
            ? getenv('TELESCOPE_ENABLED')
            : false),
    FILTER_VALIDATE_BOOLEAN
);
 
if (
    in_array($appEnv, ['local', 'staging'], true)
&& $telescopeEnabled
&& class_exists(\Laravel\Telescope\TelescopeApplicationServiceProvider::class)
) {
    $providers[] = \App\Providers\TelescopeServiceProvider::class;
}
 
return $providers;