FROM php:8.0.5-fpm

LABEL version="1.0.0"

RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    libzip-dev \
    libonig-dev \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl
RUN docker-php-ext-configure gd --enable-gd --with-freetype=/usr/include/ --with-jpeg=/usr/include/
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
#RUN alias composer='php composer.phar'


WORKDIR /var/www/html

COPY composer.json composer.lock ./

COPY . .
#copy environment file
COPY ./.env.example ./.env

#RUN composer install
CMD sh -c "composer install --ignore-platform-reqs && php artisan key:generate --ansi"

CMD sh -c "php artisan migrate"
CMD sh -c "php artisan db:seed"

CMD sh -c "php artisan serve"

EXPOSE 3000