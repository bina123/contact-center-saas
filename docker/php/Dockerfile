FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    zip \
    unzip \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Disable platform checks
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_IGNORE_PLATFORM_REQS=1

WORKDIR /var/www

# Copy composer.json
COPY composer.json ./

# Install dependencies
RUN composer install --no-dev --no-scripts --prefer-dist --ignore-platform-reqs --no-interaction

# Copy application
COPY . .

# Generate autoload (with platform check disabled)
RUN composer dump-autoload --optimize --no-dev --ignore-platform-reqs

# Create directories and set permissions
RUN mkdir -p storage/{app,framework/cache/data,framework/sessions,framework/views,logs} bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data /var/www

EXPOSE 9000

CMD ["php-fpm"]
