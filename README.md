# Power of Families Wordpress Theme

## Developing

Helpful Docker tips: 
* https://developer.wordpress.com/2022/11/14/seetup-local-development-environment-for-wordpress/
* https://aschmelyun.com/blog/build-a-solid-wordpress-dev-environment-with-docker/

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

#### Set up local theme/plugin

`ln -s wordpress/wp-content/themes/power-of-families ./power-of-families`

##### Update wp-config


Add the following to `wp-config.php`

```php
define('WP_HOME', 'http://localhost:8080');
define('WP_SITEURL', 'http://localhost:8080');
```

This project uses node (make sure it is installed).

Install node dependencies with `npm install`

Run `npm run dev` while developing. This will:

* Process and compile sass (scss) files
* Run JS through a linter
* Compile JS using webpack
* Serve css and js bundles at `http://localhost:8010/assets/scripts.js` and `http://localhost:8010/assets/main.css`

Don't forget to set `WP_DEBUG` to `true` in `wp-config`.

For working with visual composer and our custom shortcodes, see: https://wpbakery.atlassian.net/wiki/pages/viewpage.action?pageId=524332

### Building ###

Run `npm run build` to package up the theme into the `build` directory, ready for ftp upload.


#### Notes
https://alexjoverm.github.io/2017/03/06/Tree-shaking-with-Webpack-2-TypeScript-and-Babel/
