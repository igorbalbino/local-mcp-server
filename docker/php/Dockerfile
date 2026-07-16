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

FROM dunglas/frankenphp:1-php8.4-alpine AS runtime

LABEL org.opencontainers.image.title="Local MCP Server"
LABEL org.opencontainers.image.description="Generic MCP Server for AI Agents"
LABEL org.opencontainers.image.source="https://github.com/igorbalbino/local-mcp-server"
LABEL org.opencontainers.image.licenses="MIT"

RUN apk add --no-cache wget \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

WORKDIR /app

COPY --from=vendor /app /app
COPY docker/php/Caddyfile /etc/caddy/Caddyfile

RUN mkdir -p storage/logs storage/cache/sessions \
    && chown -R www-data:www-data storage

EXPOSE 8080

HEALTHCHECK --interval=30s --timeout=5s --start-period=10s --retries=3 \
    CMD wget -qO- http://127.0.0.1:8080/health || exit 1

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]
