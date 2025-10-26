# Dockerfile for Laravel with Nginx and PHP-FPM (optimized for low resources)
FROM php:8.2-fpm-alpine

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    oniguruma-dev \
    libxml2-dev \
    zip \
    unzip \
    nginx \
    supervisor \
    gettext

# Clear cache
RUN apk add --no-cache pcre-dev $PHPIZE_DEPS

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files
COPY composer.json composer.lock artisan bootstrap config routes app ./

# Install PHP dependencies
RUN composer install --optimize-autoloader --no-dev --prefer-dist --no-progress --no-scripts

# Copy application code
COPY . .

# Copy PHP production configs (memory/opcache/php-fpm tuning)
COPY docker/php/zz-prod.ini /usr/local/etc/php/conf.d/zz-prod.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy static nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create supervisor config (no overload monitor)
RUN cat > /etc/supervisord.conf <<'EOF'
[supervisord]
user=root
nodaemon=true

[program:php-fpm]
command=php-fpm
autostart=true
autorestart=true

[program:nginx]
command=nginx -g "daemon off;"
autostart=true
autorestart=true
EOF

# Start supervisor with nginx config substitution
CMD /bin/sh -c 'nginx -t && /usr/bin/supervisord -c /etc/supervisord.conf'