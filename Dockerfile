# syntax=docker/dockerfile:1.7

# ============================================================================
# Stage 1 — Build asset frontend (Vite + Tailwind)
# ============================================================================
FROM node:22-alpine AS assets

WORKDIR /app

COPY package.json package-lock.json* ./
RUN npm ci --ignore-scripts

COPY vite.config.js tailwind.config.js postcss.config.js ./
COPY resources ./resources
RUN npm run build


# ============================================================================
# Stage 2 — Dependensi PHP (tanpa dev)
# ============================================================================
FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
        --no-dev \
        --no-scripts \
        --no-autoloader \
        --prefer-dist \
        --no-interaction

COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev


# ============================================================================
# Stage 3 — Runtime: nginx + php-fpm dijalankan oleh supervisord
# ============================================================================
FROM php:8.4-fpm-alpine AS runtime

ENV APP_ENV=production \
    APP_DEBUG=false \
    PHP_OPCACHE_ENABLE=1

RUN apk add --no-cache \
        nginx \
        supervisor \
        bash \
        curl \
        tzdata \
        icu-libs \
        libzip \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        icu-dev \
        libzip-dev \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_mysql \
        bcmath \
        intl \
        zip \
        opcache \
    && apk del .build-deps \
    && rm -rf /tmp/*

WORKDIR /app

# Konfigurasi
COPY docker/php/php.ini      /usr/local/etc/php/conf.d/99-maxport.ini
COPY docker/php/www.conf     /usr/local/etc/php-fpm.d/zz-maxport.conf
COPY docker/nginx/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisor/supervisord.conf
COPY docker/entrypoint.sh    /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Kode aplikasi + hasil build
COPY --chown=www-data:www-data . .
COPY --from=vendor  --chown=www-data:www-data /app/vendor       ./vendor
COPY --from=assets  --chown=www-data:www-data /app/public/build ./public/build

# .env tidak ikut ke dalam image — konfigurasi disuntikkan lewat environment.
RUN rm -f .env .env.example \
    && mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

EXPOSE 8080

# $PORT diisi platform (Railway); di Docker Compose tetap 8080.
HEALTHCHECK --interval=15s --timeout=5s --start-period=25s --retries=5 \
    CMD curl -fsS "http://127.0.0.1:${PORT:-8080}/up" || exit 1

ENTRYPOINT ["entrypoint"]
CMD ["web"]
