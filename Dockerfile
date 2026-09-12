# syntax=docker/dockerfile:1

##
## 1) Builder: installs PHP + JS dependencies and builds the app.
##    Both toolchains are needed here because the Wayfinder Vite plugin
##    shells out to `php artisan` while building the frontend assets.
##
FROM php:8.4-cli AS builder

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
        unzip \
        git \
        curl \
        ca-certificates \
        gnupg \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        zip \
    && curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Install PHP dependencies first so this layer is cached across code changes.
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --no-autoloader

# Install JS dependencies next, also cached independently of app code.
COPY package.json package-lock.json ./
RUN npm ci

COPY . .

RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# Wayfinder and the Vite build need a bootstrapped app (APP_KEY, cache
# paths, ...); none of this reaches the runtime image or a real database.
RUN cp .env.example .env \
    && php artisan key:generate --force \
    && npm run build \
    && rm .env

##
## 2) Runtime image: Apache + PHP, no Node/Composer/dev tooling.
##
FROM php:8.4-apache AS app

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libzip-dev \
        libpng-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libicu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        pdo_pgsql \
        pgsql \
        bcmath \
        exif \
        gd \
        intl \
        pcntl \
        zip \
    # The PHP extensions above dlopen() these shared libraries at runtime,
    # but apt considers them auto-installed dependencies of the -dev
    # packages; mark them manual so purging the -dev/headers below doesn't
    # cascade-remove the runtime .so files the extensions need.
    && apt-mark manual libpq5 libzip5 libpng16-16t64 libjpeg62-turbo libfreetype6 libonig5 libicu76 \
    && apt-get purge -y --auto-remove libpq-dev libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libonig-dev libicu-dev \
    && rm -rf /var/lib/apt/lists/*

# Serve the app from public/ instead of Apache's default docroot.
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' \
        /etc/apache2/sites-available/*.conf \
        /etc/apache2/apache2.conf \
        /etc/apache2/conf-available/*.conf \
    && a2enmod rewrite headers

WORKDIR /var/www/html

COPY --from=builder /app /var/www/html

RUN mkdir -p storage/framework/{cache,sessions,testing,views} storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rwX storage bootstrap/cache

ENV APP_ENV=production \
    LOG_CHANNEL=stderr \
    LOG_LEVEL=info

EXPOSE 80

# Config/route/view caches depend on runtime env vars (DB, APP_KEY, ...),
# so they are built on container start rather than at image build time.
CMD ["sh", "-c", "php artisan config:cache && php artisan route:cache && php artisan view:cache && apache2-foreground"]
