# syntax=docker/dockerfile:1.7

FROM node:24-bookworm-slim AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci --no-audit --no-fund

COPY . .
RUN npm run build

FROM composer:2.8 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader

COPY . .
RUN composer dump-autoload \
    --no-dev \
    --optimize \
    --classmap-authoritative \
    --no-interaction

FROM dunglas/frankenphp:php8.5-bookworm AS runtime

RUN install-php-extensions \
    intl \
    opcache \
    pcntl \
    pdo_mysql \
    pdo_pgsql \
    pdo_sqlite \
    zip

WORKDIR /app

COPY --chown=www-data:www-data . .
COPY --from=vendor --chown=www-data:www-data /app/vendor /app/vendor
COPY --from=frontend --chown=www-data:www-data /app/public/build /app/public/build
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/php.ini /usr/local/etc/php/conf.d/99-app.ini
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint

RUN chmod +x /usr/local/bin/app-entrypoint \
    && mkdir -p /app/storage/framework/cache/data \
        /app/storage/framework/sessions \
        /app/storage/framework/views \
        /app/storage/logs \
        /app/storage/app/private \
        /app/bootstrap/cache \
        /var/lib/chat \
        /data \
        /config \
    && chown -R www-data:www-data \
        /app/storage \
        /app/bootstrap/cache \
        /var/lib/chat \
        /data \
        /config

USER www-data

EXPOSE 80 443 443/udp

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
