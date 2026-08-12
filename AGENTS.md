# Agent Onboarding

Power of Families WordPress site. PHP 8.4 / TypeScript / Docker.

## Layout

```
wp-content/themes/power-of-families/   ← custom Genesis child theme (your main work)
wp-content/plugins/pof-bloom-plugin/   ← custom Bloom plugin
wordpress/                             ← WP core (git-ignored, pulled separately)
docker-compose.yml                     ← local dev environment
docker-compose.worktree.yml            ← optional override: share main project's DB
```

## Worktree setup

When working in a `git worktree` (under `.claude/worktrees/`), pick one of
three DB-sharing patterns from
[README → Sharing DB / WordPress data](README.md#sharing-db--wordpress-data-with-the-main-checkout):

| | DB | When |
|---|---|---|
| **A** | own copy, populated by stdin-streaming main's dump | branch work that mutates DB |
| **B** | main's running `db` container (compose override) | read-mostly, ok with shared writes |
| **C** | symlinked `db-data` via `bin/use-worktree-data` | maximum sharing, only one `db` runs at a time |

Quick recipe for **A** (recommended default — keeps main's data safe):

```sh
mv wordpress wordpress.pre-symlink && ln -s ~/projects/power-of-families/wordpress wordpress
docker compose up -d db
docker compose exec -T db sh -c \
    'mariadb -u pofuser -ppofpass poweroffamilies' \
    < ~/projects/power-of-families/db-backups/poweroffamilies.dump
npm run build:theme                       # one-time, creates wp-content/themes/power-of-families/dist/
docker compose up -d wordpress            # site at http://localhost:${WP_PORT:-8080}
```

In all three patterns, the worktree's `wp-content/themes/power-of-families`
and `wp-content/plugins/pof-bloom-plugin` are bind-mounted on top of the
shared WP install, so your theme/plugin code remains isolated to the
worktree. PHPUnit always gets its own `wordpress_tests` schema regardless
of which DB pattern you pick.

## Commands

| Task | Command |
|---|---|
| Start dev containers | `docker compose up -d wordpress` |
| Build theme JS/CSS | `npm run build:theme` |
| Build plugin JS | `npm run build:plugin` |
| Build both | `npm run build` |
| Watch theme | `npm run start:theme` |
| Watch plugin | `npm run start:plugin` |
| Run tests | `npm run test` |
| Smoke-test the site after a plugin / theme / core update | `npm run smoke` (one-time setup: `npm run smoke:install`) |

## Key Facts

- Theme is a Genesis Framework child theme. PHP classes live in `wp-content/themes/power-of-families/inc/PowerOfFamilies/` (PSR-4 autoloaded).
- Plugin JS is built via `@wordpress/scripts` (webpack) into `wp-content/plugins/pof-bloom-plugin/build/`. Never edit files in `build/` directly.
- CSS is plain PostCSS (postcss-nested, autoprefixer). Config: `postcss.config.js`.
- Linting: `prettier.config.js` and `.phpcs.xml.dist`. Use spaces, not tabs.
- Deploy: push to `main` triggers rsync of the theme directory to production via `.github/workflows/deploy.yml`.

## Agent skills

### Issue tracker

GitHub Issues on `jloosli/power-of-families`, via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Default canonical vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context layout — `CONTEXT.md` + `docs/adr/` at the repo root (neither created yet; producer skills will create them lazily). See `docs/agents/domain.md`.
