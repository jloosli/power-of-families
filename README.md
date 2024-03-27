# Power of Families Wordpress Theme

## Developing

Helpful Docker tips: 
* https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
* https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

This theme is a child theme of the [https://www.studiopress.com/themes/genesis/](Genesis Framework).

* [https://developer.wpengine.com/themes/genesis-framework/](Genesis Framework Documentation)
* [https://github.com/studiopress/genesis-sample](Sample Gensis Child Theme)

General Wordpress and wp-scripts help:

* https://wordpress.tv/2023/12/19/developer-hours-modern-wordpress-development-with-the-wp-scripts-package/

### Local Development

`docker compose up wordpress`
`sudo chown -R jloosli:staff wordpress`

#### First Time Setup

##### Import DB

Copy database backup:
`scp pof:backups-tigertech/current/mysql/poweroffamilies/poweroffamilies.dump db-backups/`

Use [https://localhost:8180](phpMyAdmin) to upload database

##### Import Themes and Plugins

`rsync -avz --exclude=~/backups-tigertech/current/www/wp-content/themes/power-of-families pof:~/backups-tigertech/current/www/wp-content/themes ./wordpress/wp-content/`
`rsync -avz pof:~/backups-tigertech/current/www/wp-content/plugins ./wordpress/wp-content/`

##### Update wp-config


Add the following to `wp-config.php`

```php
define('WP_HOME', 'http://localhost:8080');
define('WP_SITEURL', 'http://localhost:8080');
```

Set `WP_DEBUG` to `true` in `wp-config`.

For working with visual composer and our custom shortcodes, see: https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332
