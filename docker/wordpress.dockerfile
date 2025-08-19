FROM wordpress:php7.4

ENV XDEBUG_PORT=9003
ENV XDEBUG_IDEKEY=docker

RUN apt-get update && apt-get install -y \
    subversion default-mysql-client && \
    pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# COPY ../bin/install-wp-tests.sh /bin/install-wp-tests.sh
# RUN /bin/install-wp-tests.sh wordpress_test root 'password' db latest