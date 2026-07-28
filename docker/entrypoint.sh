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
    mkdir -p "$(dirname "$database_path")"
    touch "$database_path"
fi

php artisan optimize

if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    php artisan migrate --force --no-interaction
fi

exec "$@"
