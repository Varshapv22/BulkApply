FROM php:8.2-fpm

# System deps
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql mbstring zip bcmath gd \
    && rm -rf /var/lib/apt/lists/*

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Match the host user so written files aren't root-owned
ARG UID=1000
ARG GID=1000
RUN groupmod -g ${GID} www-data 2>/dev/null || true; \
    usermod -u ${UID} -g ${GID} www-data 2>/dev/null || true

COPY --chown=www-data:www-data . /var/www/html

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
