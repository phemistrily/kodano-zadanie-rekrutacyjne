FROM dunglas/frankenphp:1-php8.4

RUN install-php-extensions \
    pdo_mysql \
    intl \
    opcache \
    zip

COPY --from=composer/composer:2-bin /composer /usr/bin/composer

WORKDIR /app
