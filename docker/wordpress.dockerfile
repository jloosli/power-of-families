ARG PHP_VERSION=8.4
FROM wordpress:php${PHP_VERSION}-fpm 

ENV XDEBUG_PORT=9003
ENV XDEBUG_IDEKEY=docker

 # Line 3 for EWWW plugin 
RUN apt-get update && apt-get install -y \
    subversion default-mysql-client \
    gifsicle optipng pngquant libwebp7 libjpeg-progs optipng && \
    pecl install xdebug \
    && docker-php-ext-enable xdebug

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# COPY ../bin/install-wp-tests.sh /bin/install-wp-tests.sh
# RUN /bin/install-wp-tests.sh wordpress_test root 'password' db latest