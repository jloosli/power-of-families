# Retire the Bloom Plugin

**Status:** Approved (design only — implementation in a follow-up plan)
**Date:** 2026-08-12
**Author:** Jared Loosli (with Claude)
**Closes:** Candidate 01 of [the 2026-08-12 deepening review](../../architecture/2026-08-12-deepening-review.md)

## Problem

Candidate 01 of the architecture review found the same WordPress-settings module living in
two places — `FieldRenderer` + `Settings` in the theme, `POM_Bloom_Admin_API` +
`POM_Bloom_Settings` in the Bloom plugin. Whitespace-normalised, only 61 lines differ across
~654. The review proposed extracting one Settings Screen module behind one interface, with
theme and plugin as two adapters, and left one question open: the two artifacts deploy
separately, so a shared module needs a home — a Composer package, a third plugin, or a
declared dependency.

That question has no good answer, and it turns out it does not need one. The premise behind
it is wrong.

**There is only one consumer.** The Bloom plugin is not active in production. The review
treated "two adapters genuinely justify the seam" as the argument for extracting a shared
module; with one live adapter, the duplication should be deleted, not abstracted.

### Evidence

1. **No workflow deploys the plugin.** `.github/workflows/deploy.yml` rsyncs
   `wp-content/themes/power-of-families/` and nothing else. The plugin has no
   `composer.json`, no autoloader, and no deploy step anywhere in the repo.
2. **Production has it deactivated.** The `active_plugins` option in
   `db-backups/poweroffamilies.dump` (production snapshot, Sep 2025) lists 35 plugins. Not
   one is Bloom. `pof-programs` is active; `pom-bloom` / `pof-bloom` are not.
3. **Nothing else references it.** The theme contains zero occurrences of "bloom" — no
   shortcode call, no option read, no class reference. Neither does `tests/`. The only ties
   left are dev tooling and documentation.
4. **`setup:sync-plugins` already excludes it** when pulling plugins down from production.

Because the plugin is already inactive, deleting its code **cannot change production
behavior**. That is the core safety property of this change.

## Goals

- Delete `wp-content/plugins/pof-bloom-plugin/` and every reference to it, leaving no
  half-removed state.
- Keep `npm run build` working, because `deploy.yml` depends on it in two jobs.
- Land candidate 01's actual security finding in the one copy that survives.
- Record in the architecture review that candidate 01 was closed by retirement rather than
  extraction, without rewriting a dated snapshot.

## Non-Goals

- **No shared Settings Screen module.** No Composer package, no third plugin, no
  theme↔plugin dependency. The open question is dissolved, not answered.
- **No changes to production.** No plugin deactivation (already inactive), no DB migration,
  no content edits.
- **No cleanup of orphaned Bloom data.** Production retains ~30 `bloom-categories` terms and
  three posts containing `[bloom-program]`. These already render inert; touching live
  content is a separate decision with its own risk.
- **No rewrite of the architecture review.** It stays a snapshot of what was true on
  2026-08-12; the change gets recorded as an appended note.
- **No refactoring of the surviving theme module.** `Settings.php` and `FieldRenderer.php`
  get one escaping fix and nothing else. Candidate 02 (in flight separately) is the right
  vehicle for their interface.
- **No removal of the `setup:sync-plugins` excludes.** See below.

## The change

### 1. Delete the plugin

Remove `wp-content/plugins/pof-bloom-plugin/` — 49 files, 24 of them PHP, 2,684 PHP lines.

Git history is the archive. If Bloom is ever revived, the tree is recoverable with
`git show ba01519:wp-content/plugins/pof-bloom-plugin/...` (`ba01519` being the commit this
branch was cut from). No deprecation period is warranted for code that is already switched
off in production.

### 2. Update the references

Six files outside the plugin directory reference it — sixteen edits in total.

| File                 | Line(s) | Change                                                                                                          |
| -------------------- | ------- | --------------------------------------------------------------------------------------------------------------- |
| `package.json`       | 18      | Remove the `start:plugin` script                                                                                |
| `package.json`       | 21      | Remove the `build:plugin` script                                                                                |
| `package.json`       | 22      | **`"build"` becomes `npm run build:theme`** — see the caution below                                             |
| `docker-compose.yml` | 40–41   | Remove the plugin bind mount from the `wordpress` service                                                       |
| `docker-compose.yml` | 122–123 | Remove the plugin bind mount from the `test` service                                                            |
| `.gitignore`         | 43      | Remove `wp-content/plugins/pof-bloom-plugin/build/`                                                             |
| `.prettierignore`    | 8       | Remove the vendored `jQuery.dotdotdot-master` path                                                              |
| `README.md`          | 6       | Drop "Bloom Plugin" from Contents                                                                               |
| `README.md`          | 88      | Bind-mount prose: theme only                                                                                    |
| `README.md`          | 149     | Worktree bootstrap prose: drop the plugin `build/` mention                                                      |
| `README.md`          | 199–212 | Delete the `### Plugin` build/watch section                                                                     |
| `README.md`          | 213–217 | Delete `### Build Everything`; note in the Theme section that `npm run build` is now an alias for `build:theme` |
| `AGENTS.md`          | 9       | Remove the plugin line from the Layout block                                                                    |
| `AGENTS.md`          | 40      | Bind-mount prose: theme only                                                                                    |
| `AGENTS.md`          | 51, 54  | Remove the "Build plugin JS" and "Watch plugin" table rows                                                      |
| `AGENTS.md`          | 61      | Remove the "Plugin JS is built via `@wordpress/scripts`" fact                                                   |

**The `package.json:22` edit is the one that can break CI.** `"build"` is currently
`npm run build:theme && npm run build:plugin`, and `deploy.yml` runs `npm run build` in both
the `test-before-deploy` and `build-and-deploy` jobs. Deleting the plugin without amending
this script fails the build step and blocks deployment. (`"start"` already points only at
`start:theme`, so `start:plugin` is orphaned today and carries no such risk.)

**`setup:sync-plugins` keeps its excludes.** Line 11 excludes `pof-programs`, `pom-bloom`,
and `pof-bloom` when rsyncing plugins from the production server into
`./wordpress/wp-content/`. Production almost certainly still has the deactivated plugin
directory on disk, so dropping the exclude would pull the code we just deleted back into the
dev environment. The exclude is now load-bearing for a different reason than before, which
is worth a brief comment but not a removal.

### 3. Fix the escaping bug in the surviving copy

Candidate 01 flagged an unescaped `add_query_arg()` interpolated into an `href`, present in
**both** copies. One copy is being deleted; the other gets fixed.

`inc/PowerOfFamilies/Settings.php:280` builds the tab link with no URL argument, so
`add_query_arg()` falls back to `$_SERVER['REQUEST_URI']`, which WordPress does not escape.
Line 286 interpolates the result straight into an anchor:

```php
$html .= '<a href="' . $tab_link . '" class="' . esc_attr($class) . '">' . ...
```

becomes:

```php
$html .= '<a href="' . esc_url($tab_link) . '" class="' . esc_attr($class) . '">' . ...
```

This is the classic reflected-XSS vector around `add_query_arg()`. The settings page is
gated behind `manage_options`, so severity is moderate rather than critical — but this is
exactly the "hardening lands once" win candidate 01 was after, and after the deletion there
is exactly one place for it to land.

**Exploitable only via the path segment.** Verified empirically against WordPress 6.8.3:
`add_query_arg()` re-encodes the _query string_ it inherits from `REQUEST_URI`, so a payload
after the `?` comes back percent-encoded and harmless. The path segment before the `?` is
passed through verbatim. A request URI of
`/wp-admin/"><script>alert(1)</script>/options-general.php?page=pof_settings` therefore
renders as a live `<script>` tag in the unfixed code, and as
`/wp-admin/scriptalert(1)/script/…` once `esc_url()` is applied. This distinction matters
for the regression test: a payload placed in the query string passes with or without the
fix and proves nothing.

The other half of the drift — the plugin's copy assigning `$_POST['tab']` / `$_GET['tab']`
raw, while the theme's `get_current_tab()` whitelists and sanitises — is resolved by the
deletion itself. No code change needed.

**Regression test added** (a scope addition beyond the approved design, taken because
`Settings` had 0% coverage and this is a security fix):
`wp-content/themes/power-of-families/tests/test-Settings.php`, wired into
`wp-content/themes/power-of-families/phpunit.xml`. Three cases — tab links render, the
path-segment payload cannot break out of the `href`, and `get_current_tab()` rejects an
unknown tab. Confirmed red without the fix and green with it.

Note that the theme keeps **two** PHPUnit configs: the root `phpunit.xml` and
`wp-content/themes/power-of-families/phpunit.xml`. Only the latter runs — `docker/ci-test.sh`
`cd`s into the theme directory first. The root copy appears to be stale.

### 4. Annotate the architecture review

Append to `docs/architecture/2026-08-12-deepening-review.md` without editing the analysis:

- **Candidate 01** — a note that it was closed by retiring the second consumer; the shared
  module was never built and the open question is moot.
- **Candidate 09** — void; `POM_Bloom_Program` and its 18 partials no longer exist.
- **Candidate 02** — a one-line amendment: `class-pom-bloom-admin-api.php` is gone, so the
  untyped field-definition array now has four readers, not five.

**Candidate 08 is unaffected.** Despite its `POM_`-adjacent subject matter, its files are
`inc/PowerOfFamilies/Settings.php` and `Programs/AffiliateLinker.php` — the theme's
reflection-based program registry, not the plugin's. It survives this change intact.

Republish the artifact at the URL recorded in memory so the two copies stay in sync. Run
`npx prettier --write` on the Markdown before committing — CI gates on Prettier.

## Verification

| Check                                                                 | Proves                                        |
| --------------------------------------------------------------------- | --------------------------------------------- |
| `npm run build`                                                       | The deploy path survives — the critical check |
| `npm run test:php-ci`                                                 | PHP suite matches its pre-change baseline     |
| `npm run format:check`                                                | Prettier gate passes with the ignore removed  |
| `docker compose up -d wordpress`, load the site                       | Dev stack boots with the bind mount gone      |
| Load **Settings → POF Settings**, click between tabs                  | `esc_url()` did not break the tab nav         |
| `grep -ri "bloom" --exclude-dir=node_modules --exclude-dir=wordpress` | Only the annotated review doc matches         |

A baseline `npm run build` was captured before any edits and passes, confirming
`build:plugin` currently runs as part of `build`.

## Risks

| Risk                                                                                                                          | Likelihood     | Mitigation                                                                                                         |
| ----------------------------------------------------------------------------------------------------------------------------- | -------------- | ------------------------------------------------------------------------------------------------------------------ |
| `npm run build` breaks, blocking deploy                                                                                       | High if missed | Explicit step in the plan; verified before commit                                                                  |
| Production snapshot is stale (Sep 2025) and Bloom was reactivated                                                             | Low            | Confirmed independently by the user; plugin has no deploy path, so reactivation would break on missing files today |
| Someone later wants the Bloom code                                                                                            | Low            | Recoverable from git history; sha recorded in the review note                                                      |
| Stale `./wordpress/wp-content/plugins/pof-bloom-plugin` directory left behind in a dev environment after the mount is removed | Medium         | Harmless (WordPress ignores an inactive plugin); note it in the PR description                                     |

**Rollback:** `git revert` the deletion commit. Because production never loaded this code,
rollback carries no data or migration concerns.

## Follow-ups (not in this change)

- Candidate **02** (field-definition type) is in flight in a separate worktree and shrinks
  by one reader as a result of this change.
- Candidate **09** is void; the review annotation records this. Candidate **08** is
  theme-scoped and survives.
- Issue **#68** (coverage-reporting defects) is unrelated and unaffected. Its symptoms
  reproduced during this work: `npm run test:php-ci` reports empty test counts and 0%
  coverage while PHPUnit itself passes.
- Orphaned Bloom content in production (terms and shortcodes) is left alone deliberately.
