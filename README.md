Power of Families Wordpress Theme
------------

This repo only stores the theme files. In dev environment, it's recommended you install wordpress in a `public_html` directory. Then setup a symlink to the theme folder.

`cd public_html/wp-content/themes` (the bluon theme directory shouldn't exist yet)
`sudo ln -s ../../../theme_vivial2016 vivial2016` To create the symlink

### Developing ###

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
