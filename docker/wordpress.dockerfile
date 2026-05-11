ARG PHP_VERSION=8.4
FROM wordpress:php${PHP_VERSION}-apache

ENV XDEBUG_PORT=9003
ENV XDEBUG_IDEKEY=docker

# Install xDebug
RUN pecl install xdebug && docker-php-ext-enable xdebug

# Line 3 for EWWW plugin
RUN apt-get update && apt-get install -y \
    gifsicle optipng pngquant libwebp7 libjpeg-progs optipng mariadb-client

# WordPress permalinks: .htaccess rewrite rules require AllowOverride.
# The base wordpress:apache image leaves /var/www/ at AllowOverride None,
# so without this every non-root URL 404s.
RUN printf '<Directory /var/www/html>\n    AllowOverride All\n</Directory>\n' \
    > /etc/apache2/conf-available/wp-allowoverride.conf \
    && a2enconf wp-allowoverride

# Ship a default .htaccess in /usr/src/wordpress so the entrypoint copies
# it into the bind-mount on first run. WordPress will subsequently
# maintain the WordPress block whenever permalinks are saved; the upload
# fallback above the WordPress block proxies missing /wp-content/uploads/*
# to production so local dev sees real images without syncing the uploads
# directory.
RUN printf '%s\n' \
    '# Redirect missing uploads to poweroffamilies.com' \
    '<IfModule mod_rewrite.c>' \
    'RewriteEngine On' \
    '' \
    '# Only apply to requests under /wp-content/uploads/' \
    'RewriteCond %{REQUEST_URI} ^/wp-content/uploads/' \
    '# If the requested file does not exist' \
    'RewriteCond %{REQUEST_FILENAME} !-f' \
    '# Redirect to the same path on poweroffamilies.com' \
    'RewriteRule ^wp-content/uploads/(.*)$ https://poweroffamilies.com/wp-content/uploads/$1 [R=302,L]' \
    '</IfModule>' \
    '' \
    '# BEGIN WordPress' \
    '<IfModule mod_rewrite.c>' \
    'RewriteEngine On' \
    'RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]' \
    'RewriteBase /' \
    'RewriteRule ^index\.php$ - [L]' \
    'RewriteCond %{REQUEST_FILENAME} !-f' \
    'RewriteCond %{REQUEST_FILENAME} !-d' \
    'RewriteRule . /index.php [L]' \
    '</IfModule>' \
    '# END WordPress' \
    > /usr/src/wordpress/.htaccess \
    && chown www-data:www-data /usr/src/wordpress/.htaccess

COPY docker/xdebug.ini /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# COPY ../bin/install-wp-tests.sh /bin/install-wp-tests.sh
# RUN /bin/install-wp-tests.sh wordpress_test root 'password' db latest