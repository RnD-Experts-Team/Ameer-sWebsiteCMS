# syntax=docker/dockerfile:1
#
# Ameer's CMS (Laravel 13 + Inertia/React). One image, three roles (see docker-compose.yml):
#   web       Apache + PHP, serves the admin and the public API
#   queue     php artisan queue:work   (sends the contact-form emails)
#   scheduler php artisan schedule:work (empties the trash daily)
#
# The image is self-contained: composer install and the Vite build happen here,
# so the server only needs Docker, the .env file and the volume.

############################################################
# 1. base: PHP 8.4 + Apache + the extensions the app needs
############################################################
FROM php:8.4-apache AS base

RUN a2enmod rewrite headers expires \
 && apt-get update && apt-get install -y --no-install-recommends \
      git unzip curl ca-certificates \
      libicu-dev libzip-dev \
      libpng-dev libjpeg-dev libfreetype6-dev libwebp-dev \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j"$(nproc)" bcmath exif gd intl opcache pcntl pdo_mysql zip \
 && rm -rf /var/lib/apt/lists/*

# PHP settings (uploads are capped at 10 MB per file by the CMS itself)
RUN { \
      echo "memory_limit=512M"; \
      echo "upload_max_filesize=25M"; \
      echo "post_max_size=30M"; \
      echo "max_execution_time=120"; \
      echo "expose_php=Off"; \
      echo "opcache.enable=1"; \
      echo "opcache.validate_timestamps=0"; \
      echo "opcache.memory_consumption=128"; \
    } > /usr/local/etc/php/conf.d/zz-app.ini

# Apache: serve Laravel's public/ folder, allow its .htaccess, hide version info
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
 && sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf \
 && printf '<Directory ${APACHE_DOCUMENT_ROOT}>\n    Options -Indexes +FollowSymLinks\n    AllowOverride All\n    Require all granted\n</Directory>\nServerTokens Prod\nServerSignature Off\n' \
      > /etc/apache2/conf-available/laravel.conf \
 && a2enconf laravel

WORKDIR /var/www/html


############################################################
# 2. build: composer install + Vite build (needs PHP, because
#    Wayfinder generates its route files by running artisan)
############################################################
FROM base AS build

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY --from=node:22-bookworm-slim /usr/local/bin/node /usr/local/bin/node
COPY --from=node:22-bookworm-slim /usr/local/lib/node_modules /usr/local/lib/node_modules
RUN ln -s /usr/local/lib/node_modules/npm/bin/npm-cli.js /usr/local/bin/npm

WORKDIR /app

# Dependencies first, so they stay cached until the lock files change
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --no-scripts --no-autoloader --prefer-dist

COPY package.json package-lock.json .npmrc ./
RUN npm ci

# Application code, then autoloader + package discovery, then the frontend build
COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction \
 && npm run build \
 && rm -rf node_modules resources/js/wayfinder \
 && mkdir -p storage/app/public storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs


############################################################
# 3. runtime: PHP + app code + built assets, no node, no dev tools
############################################################
FROM base AS runtime

COPY --from=build --chown=www-data:www-data /app /var/www/html
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 80
ENTRYPOINT ["entrypoint.sh"]
CMD ["apache2-foreground"]
