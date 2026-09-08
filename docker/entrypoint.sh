#!/bin/sh
set -eu

cd /var/www

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY must be configured before the container starts." >&2
    exit 1
fi

mkdir -p \
    bootstrap/cache \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs

chown -R www-data:www-data storage bootstrap/cache
chmod -R u=rwX,g=rwX,o=rX storage bootstrap/cache

su -s /bin/sh www-data -c 'php artisan config:cache --no-interaction'
su -s /bin/sh www-data -c 'php artisan route:cache --no-interaction'
su -s /bin/sh www-data -c 'php artisan view:cache --no-interaction'

exec "$@"
