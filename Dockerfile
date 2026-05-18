# Render 用 Dockerfile (PHP 8.2 + PostgreSQL + QR(gd))
FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        libpq-dev \
        libpng-dev \
        libjpeg-dev \
        libfreetype6-dev \
        unzip \
        git \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql pgsql gd \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-scripts --no-interaction

COPY . .
RUN composer dump-autoload --optimize --no-dev

# Render は $PORT を注入する
ENV PORT=10000
EXPOSE 10000

# 起動時に migrate/seed を実行してから PHP ビルトインサーバを起動
CMD php bin/migrate run && php bin/seed run && php -S 0.0.0.0:$PORT -t public/ router.php
