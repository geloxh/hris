FROM php:8.3-fpm-alpine

# pdo_mysql: DB driver | opcache: production perf | intl: locale-aware sorting/formatting
# mbstring: required by Validator (mb_strlen) for correct multi-byte length checks
RUN apk add --no-cache icu-dev oniguruma-dev \
    && docker-php-ext-install pdo pdo_mysql opcache mbstring \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl

# Recommended opcache settings for a no-framework app with a small, stable file count
RUN { \
        echo 'opcache.enable=1'; \
        echo 'opcache.memory_consumption=128'; \
        echo 'opcache.max_accelerated_files=4000'; \
        echo 'opcache.validate_timestamps=1'; \
        echo 'opcache.revalidate_freq=2'; \
    } > /usr/local/etc/php/conf.d/opcache-recommended.ini

# Reasonable hardening defaults; override per-env with a mounted php.ini if needed
RUN { \
        echo 'expose_php=Off'; \
        echo 'display_errors=Off'; \
        echo 'log_errors=On'; \
        echo 'upload_max_filesize=10M'; \
        echo 'post_max_size=12M'; \
        echo 'memory_limit=256M'; \
    } > /usr/local/etc/php/conf.d/hardening.ini

WORKDIR /var/www/html

# Run as an unprivileged user rather than root inside the container
RUN addgroup -g 1000 www && adduser -G www -u 1000 -D www \
    && chown -R www:www /var/www/html

COPY --chown=www:www . /var/www/html

USER www

EXPOSE 9000
CMD ["php-fpm"]
