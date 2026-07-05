# Menggunakan image resmi PHP 8.3
FROM php:8.3-apache

# 1. Install dependencies sistem, Node.js (Vite), dan ekstensi PHP
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    zip \
    unzip \
    git \
    libzip-dev \
    libpq-dev \
    curl \
    # Menambahkan Node.js v20 (LTS) untuk kebutuhan NPM & Vite build
    && curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    # Mengonfigurasi dan menginstal ekstensi inti PHP
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_mysql pdo_pgsql pgsql zip gd opcache \
    # Menginstal ekstensi Redis (Sesuai REDIS_CLIENT=phpredis di .env)
    && pecl install redis \
    && docker-php-ext-enable redis

# 2. Aktifkan mod_rewrite Apache untuk routing API Laravel
RUN a2enmod rewrite

# 3. Set working directory di dalam container
WORKDIR /var/www/html

# 4. Salin semua file proyek ke dalam container
COPY . /var/www/html

# 5. Install Composer secara global
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 6. Install dependencies backend (PHP/Laravel)
RUN composer install --no-dev --optimize-autoloader

# 7. Install dependencies frontend (NPM) dan jalankan Vite Build
RUN npm install
RUN npm run build

# 8. Ubah Document Root Apache agar mengarah ke folder /public Laravel
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

# 9. Berikan hak akses (permissions) untuk folder storage dan cache
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# 10. Konfigurasi Opcache untuk performa Production
RUN echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.interned_strings_buffer=8" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.max_accelerated_files=4000" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.revalidate_freq=2" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini \
    && echo "opcache.fast_shutdown=1" >> /usr/local/etc/php/conf.d/docker-php-ext-opcache.ini

# 11. Buka port 80
EXPOSE 80
