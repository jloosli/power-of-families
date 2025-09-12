# Power of Families Wordpress Tools

## Contents

- [Power of Families Theme](power-of-families)

## First Time Setup

1. Run `docker-compose up -d wordpress` to start the containers.
1. Visit [http://localhost:8080](http://localhost:8080) to set up Wordpress.
1. Set up the admin user. It will be overwritten later.
1. Update Wordpress and database to the latest version
1. Download the database backup: `npm run setup:db-download`
1. Import the database: `npm run setup:db-import`
   - You can also use [phpMyAdmin](http://localhost:8180) to upload database. See [docker-compose.yml](docker-compose.yml) for password.
1. Sync the genesis theme: `npm run setup:sync-themes`
1. Sync the plugins: `npm run setup:sync-plugins`
1. Install composer `npm run setup:composer-install`
1. Update local user password `docker-compose run --rm wpcli user update <user> --user_pass='pass'`
1. Setup PHP testing environment

    ```shell
    docker compose exec wordpress bash bin/install-wp-tests.sh wordpress_test root 'password' db latest
    ```

1. Run tests. `npm run test:php`

## Ongoing Development

Javascript building is done with `npm run start` or `npm run build`. This will watch for changes and rebuild the JS files as needed.

## Miscellaneous

Helpful Docker tips:

- https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
- https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

This theme is a child theme of the [https://www.studiopress.com/themes/genesis/](Genesis Framework).

- [Genesis Framework Documentation](https://studiopress.github.io/genesis/)
- [Sample Gensis Child Theme](https://github.com/studiopress/genesis-sample)

Cursor AI helps:
https://github.com/snarktank/ai-dev-tasks

### Redirection

Add the following to .htaccess above the wordpress to grab images and other uploads from the server instead of the local server if they are missing.:

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
