FROM php:7.1-fpm
RUN apt-get update && apt-get install -y \
    && docker-php-ext-install pdo_mysql

RUN usermod -u 1000 www-data
RUN usermod -G staff www-data
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/bin/ --filename=composer