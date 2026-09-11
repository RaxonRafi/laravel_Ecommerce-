FROM php:8.4-cli AS php

RUN apt-get update -y && apt-get install -y --no-install-recommends \
    libicu-dev \
    libzip-dev \
    unzip zip \
    zlib1g-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    default-mysql-client \
    curl ca-certificates gnupg \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        exif \
        gd \
        intl \
        pdo_mysql \
        zip

# Node is required to build the Vite frontend assets.
RUN curl -fsSL https://deb.nodesource.com/setup_22.x | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

# Git on Windows doesn't preserve the executable bit, so set it explicitly.
RUN chmod +x Docker/entrypoint.sh

ENV PORT=8000

ENTRYPOINT ["./Docker/entrypoint.sh"]
