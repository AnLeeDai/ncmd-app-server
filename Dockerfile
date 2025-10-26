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
    supervisor

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

# Copy nginx config
COPY nginx.conf /etc/nginx/nginx.conf

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create supervisor config
RUN printf '[supervisord]\nnodaemon=true\n\n[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\n\n[program:nginx]\ncommand=nginx -g "daemon off;"\nautostart=true\nautorestart=true\n' > /etc/supervisord.conf

# Expose port 80
EXPOSE 80

# Start supervisor
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]