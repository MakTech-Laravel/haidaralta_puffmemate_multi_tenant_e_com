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
        ->toContain('[program:laravel-horizon]')
        ->toContain('[program:laravel-pulse-check]')
        ->toContain('[program:laravel-scheduler]')
        ->toContain('; [program:laravel-queue-worker]')
        ->not->toMatch('/^\[program:laravel-queue-worker\]$/m')
        ->not->toMatch('/^\[program:laravel-reverb\]$/m')
        ->not->toMatch('/^command=.*reverb:start.*$/m');
});

test('queue worker stays disabled while horizon and reverb references stay optional', function () {
    $supervisor = deploymentFile('docker/supervisord.conf');

    expect($supervisor)
        ->toContain('Horizon is the sole Redis queue consumer')
        ->toContain('; [program:laravel-queue-worker]')
        ->toContain('command=/usr/local/bin/php /var/www/artisan horizon')
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
        ->toContain('geolocation=(self)')
        ->toContain('location = /manifest.webmanifest')
        ->toContain('location = /firebase-messaging-sw.js')
        ->toContain('application/manifest+json')
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
        ->toContain('ENV CI=true')
        ->toContain('COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./')
        ->toContain('corepack enable pnpm')
        ->toContain('pnpm install --frozen-lockfile')
        ->toContain('pnpm generate:pwa-assets')
        ->toContain('pnpm run build')
        ->toContain('composer install')
        ->toContain('pcntl pdo_mysql pdo_pgsql zip')
        ->toContain('--no-dev')
        ->not->toContain('key:generate')
        ->not->toContain('npm ci')
        ->not->toMatch('/(?:^|&&|RUN)\s+npm\s+/m')
        ->and($productionStage)
        ->toContain('curl')
        ->toContain('nano')
        ->toContain('HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3')
        ->toContain('http://127.0.0.1/up')
        ->not->toContain('node_modules')
        ->not->toContain('apt-get install -y --no-install-recommends $PHPIZE_DEPS');
});
