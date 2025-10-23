FROM php:8.2-cli

# Sistem güncelleme ve gerekli paketler
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    zip \
    default-mysql-client \
    libonig-dev \
    libzip-dev \
    && docker-php-ext-install pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Composer kurulumu
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
