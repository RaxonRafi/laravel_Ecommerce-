FROM php:8.1 as php
RUN apt-get update -y && apt-get install -y \
    libicu-dev \
    libmariadb-dev \
    unzip zip \
    zlib1g-dev \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev
RUN docker-php-ext-install pdo pdo_mysql bcmath


RUN docker-php-ext-install gettext intl pdo_mysql gd


WORKDIR /var/www
COPY . .

COPY --from=composer:2.3.5 /usr/bin/composer /usr/bin/composer

RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd
RUN echo "extension=gd" > /usr/local/etc/php/conf.d/docker-php-ext-gd.ini

ENV PORT=8000
ENTRYPOINT ["docker/entrypoint.sh"]
