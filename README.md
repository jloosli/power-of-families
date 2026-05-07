# Power of Families WordPress Tools

## Contents

- [Theme](wp-content/themes/power-of-families)
- [Bloom Plugin](wp-content/plugins/pof-bloom-plugin)

## First Time Setup

> **Upgrading from the pre-worktree layout?** Earlier versions of this repo
> used hardcoded container names (`pof_wordpress`, `pof_db`, etc.) and ports.
> If you have stale `pof_*` containers from before the refactor, run
> `docker compose down` from your old project checkout (or
> `docker rm -f pof_wordpress pof_db pof_phpmyadmin pof_wpcli pof_composer pof_test`)
> before bringing up the new stack.

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

## Running Multiple Worktrees in Parallel

Each git worktree gets its own independent docker compose stack — Compose
namespaces containers by directory name, so checkouts under
`.claude/worktrees/<name>/` automatically use the project name `<name>` and
won't collide with the main checkout. To run two stacks at the same time,
each stack just needs **unique host ports**.

All shared defaults (PHP version, MariaDB version, debug flag, host ports)
live in [docker-compose.yml](docker-compose.yml) as `${VAR:-default}` fallbacks,
so a fresh checkout works without any `.env` file. Per-worktree overrides go
in a (gitignored) `.env`:

```shell
cp .env.example .env
# then edit .env:
# WP_PORT=8081
# PHPMYADMIN_PORT=8181
# XDEBUG_PORT=9103
```

Then `docker compose up -d wordpress` from the worktree directory. Visit
`http://localhost:<your WP_PORT>` — the main checkout keeps `:8080`.

### Sharing DB / WordPress data with the main checkout

The DB volume is large (~1.6 GB) and re-running first-time setup in every
worktree is wasteful. To reuse the data from the main project, run from
inside the worktree:

```shell
bin/use-worktree-data
# or, if your main project lives elsewhere:
bin/use-worktree-data /path/to/power-of-families
```

This symlinks `db-data`, `db-backups`, and `wordpress` to the main checkout
so both stacks resolve to the same on-disk files. Docker bind mounts on
macOS follow host symlinks correctly.

> **Important:** only one `db` service may run at a time when `db-data` is
> shared. Two MariaDB instances pointed at the same datadir will fail to
> lock or, worse, corrupt the data. Stop the other stack's `db`
> (`docker compose stop db` from that worktree) before starting yours, or
> edit `bin/use-worktree-data` to skip the `db-data` link and import the
> dump from the shared `db-backups` into a per-worktree `db-data`.

Without the helper script, a fresh worktree gets empty bind mounts and you
run the standard "First Time Setup" flow above to populate them.

### Reference: Docker compose env overrides

| Var                | Default | Used in                    |
| ------------------ | ------- | -------------------------- |
| `WP_PORT`          | `8080`  | wordpress host port        |
| `PHPMYADMIN_PORT`  | `8180`  | phpmyadmin host port       |
| `XDEBUG_PORT`      | `9003`  | wordpress + test xDebug    |
| `PHP_VERSION`      | `8.4`   | wordpress + test image     |
| `MARIA_DB_VERSION` | `10.11.14` | db image                |
| `WORDPRESS_DEBUG`  | `false` | `WORDPRESS_SCRIPT_DEBUG`   |

### Bumping the PHP version

`PHP_VERSION` is pinned in several places that have to stay in sync:
[docker-compose.yml](docker-compose.yml) (`${PHP_VERSION:-8.4}` default —
canonical), `wp-content/themes/power-of-families/composer.json`
(`config.platform.php`), and a `PHP_VERSION:` / `php-version:` literal in
each `.github/workflows/*.yml`. [bin/check-php-version](bin/check-php-version)
— wired into CI and runnable locally as `npm run check:php-version` —
prints every pin with its version and fails the build if any disagree,
so a bump can't land in only some files.

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
