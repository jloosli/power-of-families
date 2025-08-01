# Power of Families Wordpress Tools

## Contents

- [Power of Families Theme](power-of-families-theme)
- [Power of Families Programs](pof-programs-plugin)
- [Power of Families Bloom](pof-bloom-plugin)

### First Time Setup

#### Import DB

Copy database backup:
`rsync -avzh pof:backups-tigertech/current/mysql/poweroffamilies/poweroffamilies.dump db-backups/`

Use [phpMyAdmin](http://localhost:8180) to upload database. See [docker-compose.yml](docker-compose.yml) for password.

#### Import Themes and Plugins

```shell
rsync -avzh --exclude=~/backups-tigertech/current/www/wp-content/themes/power-of-families \
  --exclude=~/backups-tigertech/current/www/wp-content/themes/power-of-families \
  pof:~/backups-tigertech/current/www/wp-content/themes ./wordpress/wp-content/

rsync -avzh --exclude=~/backups-tigertech/current/www/wp-content/plugins/pof-programs \
  --exclude=~/backups-tigertech/current/www/wp-content/plugins/pom-bloom \
  pof:~/backups-tigertech/current/www/wp-content/plugins ./wordpress/wp-content/
```

### Ongoing Development

Javascript building is done with `npm run start` or `npm run build`. This will watch for changes and rebuild the JS files as needed.

## Miscellaneous

Helpful Docker tips:

- https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
- https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

This theme is a child theme of the [https://www.studiopress.com/themes/genesis/](Genesis Framework).

- [Genesis Framework Documentation](https://developer.wpengine.com/themes/genesis-framework/)
- [Sample Gensis Child Theme](https://github.com/studiopress/genesis-sample)

General Wordpress and wp-scripts help:

- https://wordpress.tv/2023/12/19/developer-hours-modern-wordpress-development-with-the-wp-scripts-package/

