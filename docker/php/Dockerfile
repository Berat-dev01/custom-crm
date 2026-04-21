FROM php:8.3-fpm-alpine

RUN apk add --no-cache \
    bash \
    curl \
    freetype-dev \
    g++ \
    gcc \
    git \
    icu-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    linux-headers \
    make \
    mysql-client \
    nodejs \
    npm \
    oniguruma-dev \
    openssh-client \
    unzip \
    zip \
    $PHPIZE_DEPS

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        bcmath \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_mysql \
        zip

RUN pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN git config --system url."https://github.com/".insteadOf "git@github.com:"

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data
