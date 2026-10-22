# syntax=docker/dockerfile:1.7
#
# Coolify: git source + auto-deploy on push. Self-contained (no manual Coolify pack switch).
# When only app code changes, BuildKit keeps `runtime-base` CACHED → no PHP recompile.
#
# Local:  docker build -t app:local .
# Shared base (optional, other projects / GHCR):
#   docker build -f Dockerfile.base -t php-runtime:8.4 .
#
# docker/nginx.conf, docker/entrypoint.sh, docker/supervisord.conf — unchanged; no edits needed.

# Keep in sync with Dockerfile.base
FROM php:8.4-fpm-bookworm AS runtime-base

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        ca-certificates \
        curl \
        gnupg \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl mbstring pcntl pdo_mysql pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable opcache redis \
    && curl -fsSL https://www.postgresql.org/media/keys/ACCC4CF8.asc \
        | gpg --dearmor -o /usr/share/keyrings/postgresql-archive-keyring.gpg \
    && echo "deb [signed-by=/usr/share/keyrings/postgresql-archive-keyring.gpg] http://apt.postgresql.org/pub/repos/apt bookworm-pgdg main" \
        > /etc/apt/sources.list.d/pgdg.list \
    && apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6 \
        libicu72 \
        libjpeg62-turbo \
        libonig5 \
        libpng16-16 \
        libpq5 \
        libwebp7 \
        libzip4 \
        nginx \
        postgresql-client-18 \
        procps \
        supervisor \
    && apt-get purge -y --auto-remove \
        $PHPIZE_DEPS \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libwebp-dev \
        libzip-dev \
    && rm -rf /var/lib/apt/lists/* /tmp/pear \
    && rm -f /etc/nginx/sites-enabled/default

WORKDIR /var/www

FROM runtime-base AS build

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# Minimal Node for Wayfinder (needs PHP) + Vite — not a full /usr/local dump.
COPY --from=node:24-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:24-bookworm-slim /usr/local/lib/node_modules/corepack /usr/local/lib/node_modules/corepack
RUN ln -sf ../lib/node_modules/corepack/dist/corepack.js /usr/local/bin/corepack

WORKDIR /var/www

# Build-only placeholder — never the production APP_KEY. Wayfinder/artisan need a key.
ENV CI=true \
    APP_KEY=base64:YWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWFhYWE=

COPY composer.json composer.lock ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install \
        --no-dev \
        --no-interaction \
        --no-progress \
        --no-scripts \
        --prefer-dist

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN --mount=type=cache,target=/root/.local/share/pnpm/store \
    corepack enable pnpm \
    && corepack install \
    && pnpm install --frozen-lockfile

COPY . .

ARG VITE_APP_NAME=App
ARG VITE_ERROR_BOUNDARY_DEBUG=false
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https
ARG VITE_FIREBASE_API_KEY
ARG VITE_FIREBASE_AUTH_DOMAIN
ARG VITE_FIREBASE_PROJECT_ID
ARG VITE_FIREBASE_STORAGE_BUCKET
ARG VITE_FIREBASE_MESSAGING_SENDER_ID
ARG VITE_FIREBASE_APP_ID
ARG VITE_FIREBASE_MEASUREMENT_ID
ARG VITE_FIREBASE_VAPID_KEY

# Runtime-only (not Vite): FCM_SW_SHOW_NOTIFICATION — Coolify service env / production ENV below.
# PWA icons/splashes are committed under public/pwa/ — do not regenerate in the image build.

ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_ERROR_BOUNDARY_DEBUG=${VITE_ERROR_BOUNDARY_DEBUG} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME} \
    VITE_FIREBASE_API_KEY=${VITE_FIREBASE_API_KEY} \
    VITE_FIREBASE_AUTH_DOMAIN=${VITE_FIREBASE_AUTH_DOMAIN} \
    VITE_FIREBASE_PROJECT_ID=${VITE_FIREBASE_PROJECT_ID} \
    VITE_FIREBASE_STORAGE_BUCKET=${VITE_FIREBASE_STORAGE_BUCKET} \
    VITE_FIREBASE_MESSAGING_SENDER_ID=${VITE_FIREBASE_MESSAGING_SENDER_ID} \
    VITE_FIREBASE_APP_ID=${VITE_FIREBASE_APP_ID} \
    VITE_FIREBASE_MEASUREMENT_ID=${VITE_FIREBASE_MEASUREMENT_ID} \
    VITE_FIREBASE_VAPID_KEY=${VITE_FIREBASE_VAPID_KEY}

RUN composer dump-autoload --classmap-authoritative --no-dev --no-interaction \
    && pnpm run build \
    && rm -rf node_modules

FROM runtime-base AS production

COPY --from=build /var/www /var/www

COPY docker/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/application-entrypoint

RUN chmod 0755 /usr/local/bin/application-entrypoint \
    && mkdir -p /run/nginx /var/log/supervisor \
        /var/www/storage/framework/cache/data \
        /var/www/storage/framework/sessions \
        /var/www/storage/framework/views \
        /var/www/storage/logs \
        /var/www/bootstrap/cache \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

WORKDIR /var/www

ENV QUEUE_CONNECTION=redis \
    QUEUE_NAMES=default \
    QUEUE_SLEEP=3 \
    QUEUE_TRIES=3 \
    QUEUE_TIMEOUT=600 \
    QUEUE_MAX_TIME=3600 \
    QUEUE_MEMORY=128 \
    QUEUE_PROCESSES=2 \
    FCM_SW_SHOW_NOTIFICATION=false \
    PWA_SW_ENABLED=true

EXPOSE 80

HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 \
    CMD ["curl", "--fail", "--silent", "--show-error", "--max-time", "5", "--output", "/dev/null", "http://127.0.0.1/up"]

ENTRYPOINT ["/usr/local/bin/application-entrypoint"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
