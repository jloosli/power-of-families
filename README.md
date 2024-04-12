# Power of Families Wordpress Tools

## Contents

- [Power of Families Theme](power-of-families-theme)
- [Power of Families Programs](pof-programs-plugin)
- [Power of Families Bloom](pof-bloom-plugin)

## Developing

Helpful Docker tips:

- https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
- https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

This theme is a child theme of the [https://www.studiopress.com/themes/genesis/](Genesis Framework).

- [Genesis Framework Documentation](https://developer.wpengine.com/themes/genesis-framework/)
- [Sample Gensis Child Theme](https://github.com/studiopress/genesis-sample)

General Wordpress and wp-scripts help:

- https://wordpress.tv/2023/12/19/developer-hours-modern-wordpress-development-with-the-wp-scripts-package/

### Local Development

Copy `ZscalerRootCA.crt` to [docker](docker)

`docker compose up wordpress`
`sudo chown -R j-lo:staff wordpress`

#### First Time Setup

##### Import DB

Copy database backup:
`rsync -avzh pof:backups-tigertech/current/mysql/poweroffamilies/poweroffamilies.dump db-backups/`

Use [phpMyAdmin](http://localhost:8180) to upload database

##### Import Themes and Plugins

`rsync -avzh --exclude=~/backups-tigertech/current/www/wp-content/themes/power-of-families pof:~/backups-tigertech/current/www/wp-content/themes ./wordpress/wp-content/`
`rsync -avzh --exclude=~/backups-tigertech/current/www/wp-content/plugins/pof-programs --exclude=~/backups-tigertech/current/www/wp-content/plugins/pom-bloom pof:~/backups-tigertech/current/www/wp-content/plugins ./wordpress/wp-content/`

##### Update wp-config

Add the following to `wp-config.php`

```php
define('WP_HOME', 'http://localhost:8080');
define('WP_SITEURL', 'http://localhost:8080');
```

Set `WP_DEBUG` to `true` in `wp-config`.

For working with visual composer and our custom shortcodes, see: https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332

```
export BASE_DIR="backups-tigertech/2024-03-23T02:16:39-07:00/www/"; rsync -avzhn --exclude ${BASE_DIR}wp-content/uploads --exclude ${BASE_DIR}wp-content/media pof:${BASE_DIR} ./wordpress
export BASE_DIR="backups-tigertech/2024-03-23T02:16:39-07:00/"; rsync -avzh pof:${BASE_DIR}mysql/poweroffamilies/poweroffamilies.dump .
```