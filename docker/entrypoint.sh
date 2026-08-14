#!/bin/sh
set -e

# Laravel needs an APP_KEY. There is no .env in the image, so read it from the
# environment and fall back to an ephemeral one (which invalidates sessions on
# restart -- set APP_KEY in the environment to avoid that).
if [ -z "$APP_KEY" ]; then
    APP_KEY="$(php artisan key:generate --show)"
    export APP_KEY
    echo "entrypoint: no APP_KEY given, generated an ephemeral one for this container."
fi

# SQLite lives on a volume, so the file may not exist yet on a fresh container.
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    : "${DB_DATABASE:=/app/database/database.sqlite}"
    touch "$DB_DATABASE"
fi

# Passport signs its OAuth tokens with a keypair. Same story as APP_KEY: pass one in through
# the environment, or get an ephemeral one that lasts as long as the container -- which asks
# every connected client to authorise again after a restart.
if [ -z "$PASSPORT_PRIVATE_KEY" ] && [ ! -f /app/storage/oauth-private.key ]; then
    php artisan passport:keys --no-interaction
    echo "entrypoint: no Passport keys given, generated an ephemeral pair for this container."
fi

php artisan migrate --force --graceful
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec "$@"
