# Use PHP 8.2 with Apache
FROM php:8.2-apache

# Install system dependencies (skip Node.js since no FE build)
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions required by Laravel
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy the rest of the application (including artisan)
COPY . .

# Create dummy .env for composer scripts
RUN echo "APP_NAME=Laravel" > .env && \
    echo "APP_ENV=production" >> .env && \
    echo "APP_KEY=" >> .env

# Install PHP dependencies (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --ignore-platform-reqs

# Skip frontend build since we only deploy backend

# Copy config files
COPY php.ini /usr/local/etc/php/conf.d/custom.ini
COPY apache-config.conf /etc/apache2/sites-available/000-default.conf

# Copy entrypoint script
COPY entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Set permissions for Laravel
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Expose port 80 (Render will handle port mapping)
EXPOSE 80

# Use entrypoint script
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]