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

test('the dockerfile is self-contained for Coolify git auto-deploy and keeps tooling out of production', function () {
    $dockerfile = deploymentFile('Dockerfile');
    $base = deploymentFile('Dockerfile.base');
    $productionStage = str($dockerfile)->after('AS production')->toString();
    $runtimeBaseStage = str($dockerfile)->after('AS runtime-base')->before('FROM runtime-base AS build')->toString();

    expect($base)
        ->toContain('pcntl pdo_mysql pdo_pgsql zip')
        ->toContain('postgresql-client-18')
        ->toContain('procps')
        ->toContain('libwebp7')
        ->toContain('nginx')
        ->toContain('supervisor')
        ->toContain('php-runtime')
        ->not->toContain('nano')
        ->not->toContain('maktech-laravel')
        ->not->toContain('puffmemate-php')
        ->not->toContain('node:');

    expect($dockerfile)
        ->toContain('FROM php:8.4-fpm-bookworm AS runtime-base')
        ->toContain('FROM runtime-base AS build')
        ->toContain('FROM runtime-base AS production')
        ->toContain('ENV CI=true')
        ->toContain('COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./')
        ->toContain('corepack enable pnpm')
        ->toContain('pnpm install --frozen-lockfile')
        ->toContain('pnpm run build')
        ->toContain('composer install')
        ->toContain('--no-dev')
        ->toContain('/usr/local/bin/node')
        ->toContain('/usr/local/lib/node_modules/corepack')
        ->toContain('ARG VITE_APP_NAME=App')
        ->not->toContain('COPY --from=node:24-bookworm-slim /usr/local/ /usr/local/')
        ->not->toContain('pnpm generate:pwa-assets')
        ->not->toContain('key:generate')
        ->not->toContain('npm ci')
        ->not->toContain('maktech-laravel')
        ->not->toMatch('/(?:^|&&|RUN)\s+npm\s+/m')
        ->and($runtimeBaseStage)
        ->toContain('$PHPIZE_DEPS')
        ->toContain('postgresql-client-18')
        ->and($productionStage)
        ->toContain('HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3')
        ->toContain('http://127.0.0.1/up')
        ->not->toContain('node_modules')
        ->not->toContain('nano')
        ->not->toContain('$PHPIZE_DEPS')
        ->not->toContain('pnpm ')
        ->not->toContain('corepack ');
});

test('the reusable base dockerfile matches the runtime-base stage contract', function () {
    $base = deploymentFile('Dockerfile.base');

    expect($base)
        ->toContain('$PHPIZE_DEPS')
        ->toContain('docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp')
        ->toContain('pecl install redis')
        ->toContain('postgresql-client-18')
        ->toContain('procps')
        ->toContain('php-runtime');
});
