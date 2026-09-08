<?php

function deploymentFile(string $path): string
{
    $contents = file_get_contents(base_path($path));

    expect($contents)->not->toBeFalse();

    return $contents;
}

test('the production container runs only the required application processes', function () {
    $supervisor = deploymentFile('docker/supervisord.conf');

    expect($supervisor)
        ->toContain('[program:php-fpm]')
        ->toContain('[program:nginx]')
        ->toContain('[program:laravel-queue-worker]')
        ->toContain('[program:laravel-scheduler]')
        ->not->toMatch('/^\[program:laravel-(?:horizon|reverb)\]$/m')
        ->not->toMatch('/^command=.*(?:horizon|reverb:start).*$/m');
});

test('optional horizon and reverb programs remain disabled references', function () {
    $supervisor = deploymentFile('docker/supervisord.conf');

    expect($supervisor)
        ->toContain('; [program:laravel-horizon]')
        ->toContain('; command=/usr/local/bin/php /var/www/artisan horizon')
        ->toContain('disable laravel-queue-worker first')
        ->toContain('; [program:laravel-reverb]')
        ->toContain('; command=/usr/local/bin/php /var/www/artisan reverb:start')
        ->toContain('; autostart=false');
});

test('normal container startup does not mutate deployment state', function () {
    $entrypoint = deploymentFile('docker/entrypoint.sh');

    expect($entrypoint)
        ->not->toContain('key:generate')
        ->not->toContain('migrate')
        ->not->toContain('db:seed')
        ->not->toContain('printenv')
        ->not->toContain('cache:clear')
        ->toContain('exec "$@"');
});

test('nginx sends health checks through Laravel and has no local websocket proxy', function () {
    $nginx = deploymentFile('docker/nginx.conf');

    expect($nginx)
        ->toContain('try_files $uri $uri/ /index.php?$query_string;')
        ->toContain('fastcgi_pass 127.0.0.1:9000;')
        ->not->toContain('proxy_pass http://127.0.0.1:8080')
        ->not->toMatch('/location\s+=?\s*\/up\s*\{[^}]*return\s+200/s');
});

test('php fpm receives runtime environment variables', function () {
    expect(deploymentFile('docker/php-fpm.conf'))
        ->toContain('clear_env = no')
        ->toContain('listen = 127.0.0.1:9000');
});

test('the final image excludes frontend and development tooling', function () {
    $dockerfile = deploymentFile('Dockerfile');
    $productionStage = str($dockerfile)->after('FROM php:8.4-fpm-bookworm AS production')->toString();

    expect($dockerfile)
        ->toContain('COPY --from=node:24-bookworm-slim')
        ->toContain('composer install')
        ->toContain('pcntl pdo_mysql pdo_pgsql zip')
        ->toContain('--no-dev')
        ->not->toContain('key:generate')
        ->and($productionStage)
        ->not->toContain('node_modules')
        ->not->toContain('apt-get install -y --no-install-recommends $PHPIZE_DEPS');
});
