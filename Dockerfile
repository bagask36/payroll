# ==============================================================================
# Stage 1: Composer Dependencies (Production Only)
# ==============================================================================
FROM composer:2 AS composer-builder

WORKDIR /app

COPY composer.json composer.lock ./

# Install vendor packages without dev dependencies and without running artisan scripts
RUN composer install \
    --no-dev \
    --no-interaction \
    --prefer-dist \
    --no-autoloader \
    --no-scripts

COPY app/ ./app/
COPY bootstrap/ ./bootstrap/
COPY config/ ./config/
COPY database/ ./database/
COPY routes/ ./routes/
COPY artisan ./

# Generate optimized autoloader without executing scripts
RUN composer dump-autoload --optimize --no-dev --classmap-authoritative --no-scripts

# ==============================================================================
# Stage 2: Build Frontend Assets (Vite + Tailwind CSS v4)
# ==============================================================================
FROM node:22-alpine AS node-builder

WORKDIR /app

# Cache package installation
COPY package.json package-lock.json ./
RUN npm ci

# Copy application files needed for Vite compilation
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

# Copy vendor from composer-builder for Tailwind @import / @source (Flux UI & PowerGrid)
COPY --from=composer-builder /app/vendor/ ./vendor/

RUN npm run build

# ==============================================================================
# Stage 3: PHP-FPM Production Runtime (App Container)
# ==============================================================================
FROM php:8.4-fpm-alpine AS app

LABEL maintainer="RS Juwita IT Team"
LABEL description="SIM SDM RS Juwita Production Container"

# Install composer binary & php extension installer helper
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install essential PHP extensions for Laravel 13, PostgreSQL, Redis, and media/export
RUN apk add --no-cache \
        bash \
        curl \
        postgresql-client \
        su-exec \
        shadow \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions \
        pdo_pgsql \
        pgsql \
        redis \
        gd \
        zip \
        intl \
        bcmath \
        opcache \
        pcntl \
        exif

# Configure PHP runtime
COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

WORKDIR /var/www/html

# Copy application source code
COPY . /var/www/html

# Copy compiled frontend assets from Stage 2
COPY --from=node-builder /app/public/build /var/www/html/public/build

# Copy vendor dependencies from Stage 1
COPY --from=composer-builder /app/vendor /var/www/html/vendor

# Copy entrypoint script
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Setup directory permissions for Laravel and run package discovery
RUN mkdir -p /var/www/html/storage/framework/cache/data \
             /var/www/html/storage/framework/sessions \
             /var/www/html/storage/framework/views \
             /var/www/html/storage/logs \
             /var/www/html/storage/app/public \
             /var/www/html/bootstrap/cache \
    && php artisan package:discover --ansi \
    && chown -R www-data:www-data /var/www/html \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
CMD ["php-fpm"]

# ==============================================================================
# Stage 4: Nginx Web Server (Web Container)
# ==============================================================================
FROM nginx:alpine AS web

LABEL maintainer="RS Juwita IT Team"
LABEL description="SIM SDM RS Juwita Nginx Web Server"

COPY docker/nginx/default.conf /etc/nginx/conf.d/default.conf

# Copy public directory with compiled Vite assets
COPY --from=app /var/www/html/public /var/www/html/public

WORKDIR /var/www/html

EXPOSE 80

CMD ["nginx", "-g", "daemon off;"]
