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

When working in a `git worktree` (under `.claude/worktrees/`), share the
main project's running database container instead of standing up a
separate DB per worktree (saves disk + import time):

```sh
ln -s docker-compose.worktree.yml docker-compose.override.yml
docker compose up -d wordpress    # site at http://localhost:8081
```

Prereq: the main project's db container must be running
(`cd ~/projects/power-of-families && docker compose up -d db`).

Caveat: writes from the worktree mutate the shared site DB. PHPUnit
creates a separate `wordpress_tests` schema on the same mysql instance,
so test runs are isolated from site data.

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

## Key Facts

- Theme is a Genesis Framework child theme. PHP classes live in `wp-content/themes/power-of-families/inc/PowerOfFamilies/` (PSR-4 autoloaded).
- Plugin JS is built via `@wordpress/scripts` (webpack) into `wp-content/plugins/pof-bloom-plugin/build/`. Never edit files in `build/` directly.
- CSS is plain PostCSS (postcss-nested, autoprefixer). Config: `postcss.config.js`.
- Linting: `prettier.config.js` and `.phpcs.xml.dist`. Use spaces, not tabs.
- Deploy: push to `main` triggers rsync of the theme directory to production via `.github/workflows/deploy.yml`.
