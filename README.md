# Power of Families WordPress Tools

## Contents

- [Theme](wp-content/themes/power-of-families)
- [Bloom Plugin](wp-content/plugins/pof-bloom-plugin)

## First Time Setup

1. Run `docker compose up -d wordpress` to start the containers.
1. Visit [http://localhost:8080](http://localhost:8080) to set up WordPress.
1. Set up the admin user. It will be overwritten later.
1. Update WordPress and database to the latest version.
1. Download the database backup: `npm run setup:db-download`
1. Import the database: `npm run setup:db-import`
   - You can also use [phpMyAdmin](http://localhost:8180) to upload the database. See [docker-compose.yml](docker-compose.yml) for credentials.
1. Sync the genesis theme: `npm run setup:sync-themes`
1. Sync the plugins: `npm run setup:sync-plugins`
1. Install composer dependencies: `npm run setup:composer-install`
1. Update local user password: `docker compose run --rm wpcli user update <user> --user_pass='pass' --skip-plugins`
1. Set up the PHP testing environment:

    ```shell
    docker compose exec wordpress bash bin/install-wp-tests.sh wordpress_test root 'password' db latest
    ```

1. Run tests: `npm run test:php`

## Ongoing Development

### Theme

Watch and rebuild JS/CSS on change:

```shell
npm run start:theme
```

Build for production:

```shell
npm run build:theme
```

### Plugin

Watch and rebuild JS on change:

```shell
npm run start:plugin
```

Build for production:

```shell
npm run build:plugin
```

### Build Everything

```shell
npm run build
```

## Miscellaneous

Helpful Docker tips:

- https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
- https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

The theme is a child theme of the [Genesis Framework](https://www.studiopress.com/themes/genesis/).

- [Genesis Framework Documentation](https://studiopress.github.io/genesis/)
- [Sample Genesis Child Theme](https://github.com/studiopress/genesis-sample)

### Redirection

Add the following to `.htaccess` above the WordPress block to proxy missing uploads from production:

```apacheconf
# Redirect missing uploads to poweroffamilies.com
<IfModule mod_rewrite.c>
RewriteEngine On

# Only apply to requests under /wp-content/uploads/
RewriteCond %{REQUEST_URI} ^/wp-content/uploads/
# If the requested file does not exist
RewriteCond %{REQUEST_FILENAME} !-f
# Redirect to the same path on poweroffamilies.com
RewriteRule ^wp-content/uploads/(.*)$ https://poweroffamilies.com/wp-content/uploads/$1 [R=302,L]
</IfModule>
```
