FROM php:8.2-fpm-bookworm

RUN apt-get update && apt-get install -y --no-install-recommends \
        git unzip libpq-dev libicu-dev \
    && docker-php-ext-install -j$(nproc) intl opcache pdo_pgsql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Symfony écrit dans var/cache en dev
RUN usermod -u 1000 www-data || true \
    && chown -R www-data:www-data /var/www/html
