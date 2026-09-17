FROM php:8.4-cli
RUN apt-get update && apt-get install -y --no-install-recommends libonig-dev libzip-dev unzip && docker-php-ext-install pdo_mysql mbstring zip && rm -rf /var/lib/apt/lists/*
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY . .
RUN composer install --no-dev --no-interaction --optimize-autoloader && chown -R www-data:www-data storage bootstrap/cache
USER www-data
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
