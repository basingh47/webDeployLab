FROM php:8.4-fpm-alpine

# Alpine PHP already has what Laravel needs (pdo_sqlite, mbstring, xml, ...); opcache makes it faster
RUN docker-php-ext-install opcache

# composer, to install vendor/ inside the container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html
