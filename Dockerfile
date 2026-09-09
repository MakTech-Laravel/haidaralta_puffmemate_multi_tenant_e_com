FROM php:8.4-fpm-bookworm AS build

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        $PHPIZE_DEPS \
        git \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libzip-dev \
        unzip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" bcmath gd intl mbstring pcntl pdo_mysql pdo_pgsql zip \
    && pecl install redis \
    && docker-php-ext-enable opcache redis \
    && rm -rf /var/lib/apt/lists/* /tmp/pear

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=node:24-bookworm-slim /usr/local/ /usr/local/

WORKDIR /var/www

ENV CI=true

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

COPY package.json pnpm-lock.yaml pnpm-workspace.yaml .npmrc ./
RUN corepack enable pnpm \
    && corepack install \
    && pnpm install --frozen-lockfile

COPY . .

ARG VITE_APP_NAME=Puffonmate
ARG VITE_ERROR_BOUNDARY_DEBUG=false
ARG VITE_REVERB_APP_KEY
ARG VITE_REVERB_HOST
ARG VITE_REVERB_PORT=443
ARG VITE_REVERB_SCHEME=https

ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_ERROR_BOUNDARY_DEBUG=${VITE_ERROR_BOUNDARY_DEBUG} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME}

RUN composer dump-autoload --classmap-authoritative --no-dev --no-interaction \
    && pnpm run build

FROM php:8.4-fpm-bookworm AS production

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libfreetype6 \
        libicu72 \
        libjpeg62-turbo \
        libonig5 \
        libpng16-16 \
        libpq5 \
        libzip4 \
        nginx \
        supervisor \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

COPY --from=build /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=build /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/
COPY --from=build --chown=www-data:www-data /var/www /var/www

COPY docker/php.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-production.conf
COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/entrypoint.sh /usr/local/bin/application-entrypoint

RUN chmod 0755 /usr/local/bin/application-entrypoint \
    && mkdir -p /run/nginx /var/log/supervisor \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

WORKDIR /var/www

ENV QUEUE_CONNECTION=redis \
    QUEUE_NAMES=default \
    QUEUE_SLEEP=3 \
    QUEUE_TRIES=3 \
    QUEUE_TIMEOUT=60 \
    QUEUE_MAX_TIME=3600 \
    QUEUE_MEMORY=128 \
    QUEUE_PROCESSES=2

EXPOSE 80

ENTRYPOINT ["/usr/local/bin/application-entrypoint"]
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
