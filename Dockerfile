FROM php:8.3-apache AS base

ENV APACHE_DOCUMENT_ROOT /var/www/html/public

RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf
RUN a2enmod rewrite

RUN apt update && apt install -y git zip

COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions mysqli pdo pdo_mysql mbstring exif pcntl bcmath gd

VOLUME [ "/var/www/html/upload", "/var/www/html/application/logs" ]

WORKDIR /var/www/html

COPY --chown=www-data:www-data . .

#
# Development
#
FROM base AS local

# Install nodejs 18 - https://github.com/nodesource/distributions
# RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - &&\
#     apt-get install -y nodejs && npm -g install yarn

RUN install-php-extensions xdebug

COPY ./docker/php/docker-php-ext-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

#
# Production
#
FROM base AS prod
