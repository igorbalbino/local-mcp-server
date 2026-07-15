# syntax=docker/dockerfile:1

FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock* ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts

FROM php:8.4-cli-alpine AS runtime

RUN apk add --no-cache wget

WORKDIR /app

COPY --from=vendor /app /app

RUN mkdir -p storage/logs storage/cache/sessions \
    && chown -R www-data:www-data storage

USER www-data

ENV APP_ENV=production
EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/health || exit 1

CMD ["php", "-S", "0.0.0.0:8080", "-t", "public", "public/index.php"]
