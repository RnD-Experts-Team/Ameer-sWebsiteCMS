#!/bin/sh
# Container start-up for all three roles. The `web` service sets RUN_SETUP=true,
# which is the one place that runs migrations and creates the storage link.
set -e
cd /var/www/html

# Run artisan as www-data even when the container starts as root,
# so files written to storage/ never end up owned by root.
artisan() {
    if [ "$(id -u)" = "0" ]; then
        runuser -u www-data -- php artisan "$@"
    else
        php artisan "$@"
    fi
}

if [ ! -s .env ]; then
    echo "ERROR: /var/www/html/.env is missing or empty. Mount your production .env file." >&2
    exit 1
fi

if [ "$(id -u)" = "0" ]; then
    # storage/ is a volume: it starts empty, so create what Laravel expects
    mkdir -p storage/app/public \
             storage/framework/cache/data storage/framework/sessions storage/framework/views \
             storage/logs storage/database
    [ -f storage/database/database.sqlite ] || touch storage/database/database.sqlite
    chown -R www-data:www-data storage bootstrap/cache
fi

# Cache config, events and views (reads the mounted .env).
# Routes are not cached: routes/settings.php uses a closure route, which route:cache refuses.
artisan config:cache
artisan event:cache
artisan view:cache

if [ "${RUN_SETUP:-false}" = "true" ]; then
    artisan migrate --force
    artisan storage:link --force
fi

exec "$@"
