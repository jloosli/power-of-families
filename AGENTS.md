# Agent Onboarding

Power of Families WordPress site. PHP 8.4 / TypeScript / Docker.

## Layout

```
wp-content/themes/power-of-families/   ← custom Genesis child theme (your main work)
wordpress/                             ← WP core (git-ignored, pulled separately)
docker-compose.yml                     ← local dev environment
docker-compose.worktree.yml            ← optional override: share main project's DB
```

## Worktree setup

When working in a `git worktree` (under `.claude/worktrees/`), pick one of
three DB-sharing patterns from
[README → Sharing DB / WordPress data](README.md#sharing-db--wordpress-data-with-the-main-checkout):

|       | DB                                                 | When                                          |
| ----- | -------------------------------------------------- | --------------------------------------------- |
| **A** | own copy, populated by stdin-streaming main's dump | branch work that mutates DB                   |
| **B** | main's running `db` container (compose override)   | read-mostly, ok with shared writes            |
| **C** | symlinked `db-data` via `bin/use-worktree-data`    | maximum sharing, only one `db` runs at a time |

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
is bind-mounted on top of the shared WP install, so your theme code remains
isolated to the worktree. PHPUnit always gets its own `wordpress_tests`
schema regardless of which DB pattern you pick.

## Commands

| Task                                                     | Command                                                   |
| -------------------------------------------------------- | --------------------------------------------------------- |
| Start dev containers                                     | `docker compose up -d wordpress`                          |
| Build theme JS/CSS                                       | `npm run build:theme` (aliased as `npm run build`)        |
| Watch theme                                              | `npm run start:theme` (aliased as `npm run start`)        |
| Run tests                                                | `npm run test`                                            |
| Smoke-test the site after a plugin / theme / core update | `npm run smoke` (one-time setup: `npm run smoke:install`) |

## Key Facts

- Theme is a Genesis Framework child theme. PHP classes live in `wp-content/themes/power-of-families/inc/PowerOfFamilies/` (PSR-4 autoloaded).
- Theme JS/CSS is built via `@wordpress/scripts` (webpack) into `wp-content/themes/power-of-families/dist/`. Never edit files in `dist/` directly.
- CSS is plain PostCSS (postcss-nested, autoprefixer). Config: `postcss.config.js`.
- Linting: `prettier.config.js` and `.phpcs.xml.dist`. Use spaces, not tabs.
- Deploy: push to `main` triggers rsync of the theme directory to production via `.github/workflows/deploy.yml`.
- Coverage reporting needs `xmlstarlet` on the **host** (`brew install xmlstarlet`); it is not in the containers. `bin/run-tests-ci.sh` and `bin/ci-coverage-integration.sh` abort without it rather than reporting a false 0%.
- `npm run test:php-ci` exits 0 even when PHPUnit fails. Read `test-reports/test-output.log` for the real `OK (N tests, M assertions)` line rather than trusting the summary.

## Before merging

Make sure every GitHub Copilot review comment on the PR has either been
addressed or been found unnecessary. Never merge with Copilot feedback left
unexamined — decide on each comment, and say which ones you're dismissing and
why.

```sh
gh pr view <n> --json reviews --jq '.reviews[] | "\(.author.login) [\(.state)]: \(.body)"'
gh api --paginate repos/jloosli/power-of-families/pulls/<n>/comments \
    --jq '.[] | "\(.user.login) @ \(.path):\(.line)\n\(.body)"'
```

Feedback arrives in two places and you have to read both:

- **Inline comments** (the second command) — the line-anchored findings. Keep
  `--paginate`; without it `gh api` returns only the first 30, so a busy PR
  would silently truncate and you'd think you'd seen everything.
- **The review body** (the first command) — besides the summary, this carries
  _suppressed comments_: lower-confidence findings Copilot withholds from the
  inline API. They sit in a `<summary>Suppressed comments (N)</summary>` block
  and never appear in `pulls/<n>/comments`, so an empty result from that
  endpoint does **not** mean there was no feedback. They can be perfectly
  valid — both real issues Copilot caught on #63 arrived this way, and
  following the inline API alone would have merged them unexamined. Print the
  body in full rather than truncating it.

Copilot skips generated files, so a lockfile-only PR gets "Copilot wasn't able
to review any files in this pull request" — nothing to action there.

Copilot reviews the commit it was triggered on and does **not** re-review when
you push fixes. If you want it to look again, ask explicitly:

```sh
gh pr edit <n> --add-reviewer "@copilot"
```

The `@copilot` reviewer alias needs a reasonably recent `gh` (verified on
2.97.0); if your version rejects it, upgrade rather than assuming Copilot
review is unavailable.

The new review takes a minute or two to arrive, so don't conclude nothing
happened from an immediate poll. Two traps when checking whether the request
registered:

- REST `requested_reviewers` lists only Users, never Bots, so a pending
  Copilot request reads as an empty array there. Check GraphQL
  `reviewRequests` instead — the bot's login is
  `copilot-pull-request-reviewer` — or simply wait for the review to post.
- The request works on a **merged** PR too, so a review can land after you've
  merged. Ask before merging anyway: a finding that arrives afterwards needs a
  whole new PR to act on.

So "no new comments" after a push means nobody looked again, not that the fix
was approved.

## Agent skills

### Issue tracker

GitHub Issues on `jloosli/power-of-families`, via the `gh` CLI. See `docs/agents/issue-tracker.md`.

### Triage labels

Default canonical vocabulary (`needs-triage`, `needs-info`, `ready-for-agent`, `ready-for-human`, `wontfix`). See `docs/agents/triage-labels.md`.

### Domain docs

Single-context layout — `CONTEXT.md` + `docs/adr/` at the repo root (neither created yet; producer skills will create them lazily). See `docs/agents/domain.md`.
