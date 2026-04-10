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

# Set working directory
WORKDIR /var/www/html

# Copy WordPress test installation script
COPY bin/install-wp-tests.sh /usr/local/bin/install-wp-tests.sh
RUN chmod +x /usr/local/bin/install-wp-tests.sh

# Copy test execution script
COPY bin/run-tests.sh /usr/local/bin/run-tests.sh
RUN chmod +x /usr/local/bin/run-tests.sh

# Set proper permissions
RUN chown -R www-data:www-data /var/www/html/coverage /var/www/html/test-reports

# Default command: run CI mode (setup + phpunit)
ENTRYPOINT ["/usr/local/bin/run-tests.sh"]
CMD ["ci"]
