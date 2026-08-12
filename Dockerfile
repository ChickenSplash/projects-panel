# syntax=docker/dockerfile:1

# 1. PHP dependencies.
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --prefer-dist --optimize-autoloader

# 2. Front-end build. Tailwind scans the vendor directory, so it needs the vendor stage.
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json vite.config.js ./
RUN npm ci --ignore-scripts
COPY resources ./resources
COPY --from=vendor /app/vendor ./vendor
RUN npm run build

# 3. Runtime.
# pdo_sqlite, sqlite3 and OPcache are already compiled into this image.
FROM php:8.4-cli-alpine

WORKDIR /app

COPY . .
COPY --from=vendor /app/vendor ./vendor
COPY --from=assets /app/public/build ./public/build

# The built-in server is single threaded by default; workers keep Livewire's
# round trips from queueing behind each other.
ENV PHP_CLI_SERVER_WORKERS=4

RUN mkdir -p database \
        storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
        bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache database

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

USER www-data

EXPOSE 3000

ENTRYPOINT ["entrypoint"]
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=3000", "--no-reload"]
