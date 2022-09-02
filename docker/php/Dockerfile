FROM php:8.1-cli-alpine

COPY php.ini ${PHP_INI_DIR}

RUN apk add icu-dev \
    && docker-php-ext-install intl
