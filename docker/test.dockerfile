ARG PHP_VERSION=8.4
FROM wordpress:php${PHP_VERSION}-apache

# Set environment variables for testing
ENV XDEBUG_PORT=9003
ENV XDEBUG_IDEKEY=docker
ENV PHP_MEMORY_LIMIT=2G
ENV WP_MEMORY_LIMIT=2G

# Install required packages for testing
RUN apt-get update && apt-get install -y \
    subversion \
    mariadb-client \
    git \
    unzip \
    && rm -rf /var/lib/apt/lists/*

# Install xDebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Install PHPUnit and WordPress test dependencies globally
RUN composer global require phpunit/phpunit:^9.6 yoast/phpunit-polyfills

# Add Composer global bin to PATH
ENV PATH="/root/.composer/vendor/bin:$PATH"

# Copy xDebug configuration for testing
COPY docker/test-xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Create directories for test reports and coverage
RUN mkdir -p /var/www/html/coverage /var/www/html/test-reports

# Set working directory to the theme, which is where phpunit.xml, composer.json,
# vendor/ and tests/ all live. Every caller passes `phpunit --configuration
# phpunit.xml`, so this is the CWD they all assume; from /var/www/html that
# config is unreadable. The directory is a bind-mount target at runtime.
WORKDIR /var/www/html/wp-content/themes/power-of-families

# Copy WordPress test installation script
COPY bin/install-wp-tests.sh /usr/local/bin/install-wp-tests.sh
RUN chmod +x /usr/local/bin/install-wp-tests.sh

# Copy the container-side CI test runner.
#
# Deliberately no ENTRYPOINT of our own: we inherit the base wordpress image's
# docker-entrypoint.sh, which execs its arguments verbatim for anything that is
# not apache2*/php-fpm. So `docker compose run --rm test php ...` and
# `... phpunit ...` run as written. Baking bin/run-tests.sh in as ENTRYPOINT
# swallowed those arguments and rejected them as unknown mode names.
COPY docker/ci-test.sh /usr/local/bin/ci-test.sh
RUN chmod +x /usr/local/bin/ci-test.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/coverage /var/www/html/test-reports

# Default command: run the suite. Use `ci-test.sh` for the full
# provision-then-test flow that CI needs.
CMD ["phpunit", "--configuration", "phpunit.xml"]
