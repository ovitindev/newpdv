FROM php:8.3-fpm

# Dependências de sistema (libs pra gd, zip, soap/xml usados pelo NFePHP, etc)
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    libssl-dev \
    libcurl4-openssl-dev \
    default-mysql-client \
    && rm -rf /var/lib/apt/lists/*

# Instalador de extensões PHP (mlocati/docker-php-extension-installer)
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions \
    gd \
    pdo_mysql \
    mysqli \
    mbstring \
    bcmath \
    soap \
    zip \
    intl \
    opcache \
    pcntl \
    exif

COPY opcache.ini /usr/local/etc/php/conf.d/zz-opcache-local.ini

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

RUN chown -R www-data:www-data /var/www/html

USER www-data

EXPOSE 9000

CMD ["php-fpm"]
