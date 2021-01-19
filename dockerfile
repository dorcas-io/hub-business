FROM php:7.2
RUN apt-get update -y && apt-get install -y openssl zip unzip git
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
RUN docker-php-ext-install pdo pdo_mysql
WORKDIR /var/www/html
COPY . /var/www/html
RUN composer install
CMD php artisan serve --host=0.0.0.0 --port=8088
EXPOSE 8088