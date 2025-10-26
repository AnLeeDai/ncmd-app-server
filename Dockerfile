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

# Copy nginx config template
COPY nginx.conf.template /etc/nginx/nginx.conf.template

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Create supervisor config
COPY scripts/overload-monitor.sh /usr/local/bin/overload-monitor.sh
RUN chmod +x /usr/local/bin/overload-monitor.sh

RUN printf '[supervisord]\nuser=root\nnodaemon=true\n\n[program:php-fpm]\ncommand=php-fpm\nautostart=true\nautorestart=true\n\n[program:nginx]\ncommand=nginx -g "daemon off;"\nautostart=true\nautorestart=true\n\n[program:overload-monitor]\ncommand=/usr/local/bin/overload-monitor.sh\nautostart=true\nautorestart=true\nstdout_logfile=/var/log/overload-monitor.log\nstderr_logfile=/var/log/overload-monitor.err\n' > /etc/supervisord.conf

# Start supervisor with nginx config substitution
CMD /bin/sh -c 'PORT=${PORT:-80} envsubst '\''$PORT'\'' < /etc/nginx/nginx.conf.template > /etc/nginx/nginx.conf && nginx -t && /usr/bin/supervisord -c /etc/supervisord.conf'