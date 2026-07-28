#!/bin/sh

set -eu

if [ -z "${APP_KEY:-}" ]; then
    echo "ERROR: APP_KEY is not configured in the service environment." >&2
    exit 1
fi

if [ -z "${OPENAI_API_KEY:-}" ]; then
    echo "ERROR: OPENAI_API_KEY is not configured in the service environment." >&2
    exit 1
fi

mkdir -p \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/views \
    storage/logs \
    storage/app/private \
    bootstrap/cache

if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    database_path="${DB_DATABASE:-/var/lib/chat/database.sqlite}"
    database_directory="$(dirname "$database_path")"
    mkdir -p "$database_directory"
    touch "$database_path"

    chmod 775 "$database_directory"
    chmod 664 "$database_path"

    if [ "$(id -u)" = "0" ] && id www-data >/dev/null 2>&1; then
        chown www-data:www-data "$database_directory" "$database_path"
    fi
fi

php artisan optimize

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
