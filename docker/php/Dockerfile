FROM php:8.4-fpm-alpine AS base

WORKDIR /var/www

RUN apk add --no-cache \
    icu-dev \
    libzip-dev \
    git \
    unzip \
    && docker-php-ext-install \
        intl \
        pdo_mysql \
        opcache \
        zip

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

FROM base AS development

ENV APP_ENV=dev
ENV APP_DEBUG=1

CMD ["php-fpm"]

FROM base AS production

ENV APP_ENV=prod
ENV APP_DEBUG=0

COPY backend/composer.json backend/composer.lock ./

RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --no-autoloader

COPY backend/ ./

RUN composer dump-autoload \
    --no-dev \
    --classmap-authoritative

CMD ["php-fpm"]
