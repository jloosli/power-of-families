FROM wordpress:php8.4 
# PHP version as of 9/2025: https://support.tigertech.net/php-version

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