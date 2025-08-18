FROM wordpress:php7.4

ENV XDEBUG_PORT=9003
ENV XDEBUG_IDEKEY=docker

RUN pecl install xdebug-3.1.6 \
    && docker-php-ext-enable xdebug

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini