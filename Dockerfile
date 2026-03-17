FROM php:8.4-fpm-alpine

# Set working directory
WORKDIR /var/www

# Install dependencies
RUN apk add --no-cache \
    curl \
    libpng-dev \
    libxml2-dev \
    zip \
    unzip \
    linux-headers \
    git \
    icu-dev \
    oniguruma-dev \
    libzip-dev \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-install \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    intl \
    zip

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copy existing application directory contents
COPY . /var/www

# Copy existing application directory permissions
COPY --chown=www-data:www-data . /var/www

# Change current user to www
USER www-data

# Expose port 9000 and start php-fpm server
EXPOSE 9000
CMD ["php-fpm"]
