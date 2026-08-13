# Power of Families WordPress Tools

## Contents

- [Theme](wp-content/themes/power-of-families)

## First Time Setup

> **Upgrading from the pre-worktree layout?** Earlier versions of this repo
> used hardcoded container names (`pof_wordpress`, `pof_db`, etc.) and ports.
> If you have stale `pof_*` containers from before the refactor, run
> `docker compose down` from your old project checkout (or
> `docker rm -f pof_wordpress pof_db pof_wpcli pof_composer pof_test`)
> before bringing up the new stack.

1. Run `docker compose up -d wordpress` to start the containers.
1. Visit [http://localhost:8080](http://localhost:8080) to set up WordPress.
1. Set up the admin user. It will be overwritten later.
1. Update WordPress and database to the latest version.
1. Download the database backup: `npm run setup:db-download`
1. Import the database: `npm run setup:db-import`
1. Sync the genesis theme: `npm run setup:sync-themes`
1. Sync the plugins: `npm run setup:sync-plugins`
   (excludes `pof-programs`, `pom-bloom`, and `pof-bloom` — the first is
   maintained in this repo, and the two Bloom directories are retired code
   that may still be sitting deactivated on the production server)
1. Install composer dependencies: `npm run setup:composer-install`
1. Update local user password: `docker compose run --rm wpcli user update <user> --user_pass='pass' --skip-plugins`
1. Set up the PHP testing environment:

    ```shell
    docker compose exec wordpress bash bin/install-wp-tests.sh wordpress_test root 'password' db latest
    ```

1. Run tests: `npm run test:php`

## Container runtime

The stack works under both Docker Desktop and [Podman](https://podman.io)
on macOS. Docker Desktop is the default and needs no extra setup. To use
Podman, start the machine and point the docker CLI at its socket once per
shell session:

```shell
podman machine start
export DOCKER_HOST="unix://$(podman machine inspect podman-machine-default --format '{{.ConnectionInfo.PodmanSocket.Path}}')"
```

Every `docker compose …` command in this README routes through whichever
socket `DOCKER_HOST` (or Docker Desktop's default) points at. All images
in the stack have native `arm64` builds, so nothing runs under emulation
on Apple Silicon.

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
# XDEBUG_PORT=9103
```

Then `docker compose up -d wordpress` from the worktree directory. Visit
`http://localhost:<your WP_PORT>` — the main checkout keeps `:8080`.

### Sharing DB / WordPress data with the main checkout

The DB volume is large (~1.6 GB) and re-running first-time setup in every
worktree is wasteful. Three patterns, depending on how isolated you want
to be:

|                                       | DB                       | `wordpress/` install | Use when                                                 |
| ------------------------------------- | ------------------------ | -------------------- | -------------------------------------------------------- |
| **A. Independent DB, shared install** | own copy (stdin import)  | symlink to main      | branch work that may mutate DB — keep main's data safe   |
| **B. Live-shared DB**                 | main's running container | symlink to main      | quick lookups / read-mostly work; ok with shared writes  |
| **C. Full symlink**                   | symlinked db-data        | symlink to main      | maximum sharing; only one stack's `db` may run at a time |

In all three, the worktree's bind mount of `wp-content/themes/power-of-families`
overlays the shared WP install, so your theme code stays isolated to the
worktree.

#### Pattern A — independent DB via stdin import

Streams main's dump directly into the worktree's `db` container — no copy
of the 775 MB dump file lands on disk inside the worktree.

```shell
mv wordpress wordpress.pre-symlink && ln -s ~/projects/power-of-families/wordpress wordpress
docker compose up -d db
docker compose exec -T db sh -c \
    'mariadb -u pofuser -ppofpass poweroffamilies' \
    < ~/projects/power-of-families/db-backups/poweroffamilies.dump
docker compose up -d wordpress
```

Then run the [one-time theme bootstrap](#one-time-theme-bootstrap) below.

#### Pattern B — live-shared DB via compose override

Worktree's `wordpress` and `test` services talk to the main checkout's
running `db` container instead of starting their own.

```shell
ln -s docker-compose.worktree.yml docker-compose.override.yml
ln -s ~/projects/power-of-families/wordpress wordpress   # if you also want main's WP install
docker compose up -d wordpress
```

Prereq: the main checkout's `db` container must be running
(`cd ~/projects/power-of-families && docker compose up -d db`). PHPUnit
gets a separate `wordpress_tests` schema on the same mysql instance, so
test runs stay isolated from site data — but ordinary site writes from the
worktree mutate main's data.

#### Pattern C — full symlink via `bin/use-worktree-data`

Symlinks `db-data`, `db-backups`, and `wordpress` to the main checkout in
one shot.

```shell
bin/use-worktree-data
# or, if your main project lives elsewhere:
bin/use-worktree-data /path/to/power-of-families
```

> **Important:** only one `db` service may run at a time when `db-data` is
> shared. Two MariaDB instances pointed at the same datadir will fail to
> lock or, worse, corrupt the data. Stop the other stack's `db`
> (`docker compose stop db` from that worktree) before starting yours, or
> edit `bin/use-worktree-data` to skip the `db-data` link and import the
> dump from the shared `db-backups` into a per-worktree `db-data`.

The script refuses to overwrite existing non-symlink directories at
`db-data`, `db-backups`, or `wordpress`. Move or remove them first if a
prior pattern already populated them.

#### One-time theme bootstrap

The worktree's own `wp-content/themes/power-of-families/{vendor,dist}` are
gitignored and bind-mounted as-is, so a fresh worktree needs:

```shell
docker compose run --rm composer install --no-dev   # creates theme vendor/
npm run build:theme                                 # creates theme dist/*.asset.php
```

Without any of these patterns, a fresh worktree gets empty bind mounts and
you'll need to run the standard "First Time Setup" flow above to populate
them from scratch.

### Reference: Docker compose env overrides

| Var                | Default    | Used in                        |
| ------------------ | ---------- | ------------------------------ |
| `WP_PORT`          | `8080`     | wordpress host port            |
| `XDEBUG_PORT`      | `9003`     | wordpress + test xDebug        |
| `DB_PORT`          | `3306`     | db host port (for GUI clients) |
| `PHP_VERSION`      | `8.4`      | wordpress + test image         |
| `MARIA_DB_VERSION` | `10.11.14` | db image                       |
| `WORDPRESS_DEBUG`  | `false`    | `WORDPRESS_SCRIPT_DEBUG`       |

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

`npm run build` and `npm run start` are aliases for the two commands above —
the theme is the only thing this repo builds.

### Coverage reporting prerequisites

The host-side coverage scripts (`bin/run-tests-ci.sh`,
`bin/ci-coverage-integration.sh`) read PHPUnit's `coverage/clover.xml` with
[xmlstarlet](https://xmlstar.sourceforge.net/), which is **not** bundled with
the containers and must be installed on the host:

```shell
brew install xmlstarlet                  # macOS
sudo apt-get install -y xmlstarlet       # Debian/Ubuntu
```

CI installs it explicitly in
[.github/workflows/comprehensive-tests.yml](.github/workflows/comprehensive-tests.yml).
Both scripts now abort with an install hint when it is missing — previously
they swallowed the failure and reported 0% coverage for a healthy suite.

### Smoke Testing After Updates

`bin/smoke` exercises the public site, members login + gated content, and
the WooCommerce add-to-cart → checkout-render flow, then tails the
WordPress container's PHP error log. Run it after each local plugin /
theme / core update; only mirror an update to production once it's green.

**One-time setup (per worktree, after first checkout):**

```shell
npm run smoke:install   # downloads Chromium
```

**Discovering pending updates:**

```shell
docker compose run --rm wpcli plugin list --update=available
docker compose run --rm wpcli theme list --update=available
docker compose run --rm wpcli core check-update
```

These commands print every plugin/theme/core update available — no slug
list to maintain.

**The update loop:**

```shell
# 1. Update one thing locally
docker compose run --rm wpcli plugin update <slug>   # or theme update / core update

# 2. Smoke test
npm run smoke

# 3. If green, mirror to prod
ssh pof 'cd ~/html && wp plugin update <slug>'   # WP install lives in ~/html on prod

# 4. If red, revert locally
docker compose run --rm wpcli plugin install <slug> --version=<previous> --force
```

The smoke suite is plugin-agnostic — it never sees which plugin was just
updated. Run it after any single update. Use `npm run smoke -- --strict`
to also fail on PHP warnings / notices / deprecated calls.

Test fixtures (a `smoketest@example.com` user, a `Smoke Test Product`,
and a gated members page) are seeded idempotently by `bin/smoke-fixtures`
on every run; no manual setup needed beyond `npm run smoke:install`. The
fixture script also sets `woocommerce_force_ssl_checkout=no` so the local
HTTP-only stack can render `/checkout/` — a side effect that persists on
the DB it runs against.

Design rationale and the implementation plan live in
[`docs/superpowers/specs/2026-05-14-update-smoke-testing-design.md`](docs/superpowers/specs/2026-05-14-update-smoke-testing-design.md)
and
[`docs/superpowers/plans/2026-05-14-update-smoke-testing.md`](docs/superpowers/plans/2026-05-14-update-smoke-testing.md).

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
