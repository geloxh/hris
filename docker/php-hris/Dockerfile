FROM php:8.3-fpm-alpine

RUN apk add --no-cache icu-dev libzip-dev $PHPIZE_DEPS \ && docker-php-ext-install intl pdo_mysql opcache zip

COPY --from-composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

COPY . .
RUN composer dump-autoload --optimize \ && php bin/console cache:warmup --env-prod

CMD ["php-fpm"]