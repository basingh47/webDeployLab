FROM php:8.4-fpm-alpine

# Alpine PHP already has what Laravel needs (mbstring, xml, ...); pdo_mysql talks to the mysql container, opcache makes it faster
RUN docker-php-ext-install opcache pdo_mysql

# composer, to install vendor/ inside the container
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV COMPOSER_HOME=/tmp/composer

WORKDIR /var/www/html
