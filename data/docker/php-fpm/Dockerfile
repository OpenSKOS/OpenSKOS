FROM php:7.1-fpm-alpine

ENV TERM=ansi
ENV COMPOSER_ALLOW_SUPERUSER 1


# Update all packages
RUN apk update
RUN apk upgrade
RUN apk add docker-cli
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
RUN php composer-setup.php --install-dir=/usr/bin --filename=composer
RUN rm composer-setup.php

# Add dependency libraries
RUN apk add \
  autoconf \
  build-base \
  bash \
  curl-dev \
  gettext-dev \
  git \
  icu-dev \
  libmcrypt-dev \
  libmemcached-dev \
  libxslt-dev \
  zlib-dev

# Our extension detector needs this
RUN docker-php-ext-install json

COPY data/docker/php-fpm/php-ini-overrides.ini /usr/local/etc/php/php.ini

# Install exts as defined in composer.json
COPY data/docker/scripts scripts
COPY composer.json .
RUN scripts/ext-docker-install.sh composer.json install-log
RUN scripts/ext-pecl-install.sh composer.json install-log
RUN scripts/ext-custom-install.sh composer.json install-log
RUN php -m

# Set the workdir for script usage
WORKDIR "/var/www/openskos"

# Open up fcgi port
EXPOSE 9000

