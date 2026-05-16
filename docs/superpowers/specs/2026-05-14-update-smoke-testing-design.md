# Local Smoke Testing for Plugin / Theme / Core Updates

**Status:** Approved (design only — implementation in a follow-up plan)
**Date:** 2026-05-14
**Author:** Jared Loosli (with Claude)

## Problem

Production plugins, the Genesis parent theme, and WordPress core periodically need updating. Doing those updates blind on production risks breaking the public site (homepage, blog), the WooCommerce buy flow, or the gated members area. We want to apply each update *locally first*, prove the site still works, and only then mirror the same update to production via WP-CLI — one update at a time, so any breakage is unambiguously attributable.

The repo already has Docker-based local WordPress and PHPUnit unit tests for the custom theme/plugin, but no automated way to verify that the *integrated* site still functions after a third-party plugin update. That gap is what this spec fills.

## Goals

- One command (`bin/smoke`) verifies the local site after any update.
- Covers three user journeys: public pages loading, members login + gated content, WooCommerce add-to-cart through checkout-page render.
- Catches PHP fatals and uncaught exceptions even when a page returns HTTP 200.
- Idempotent test fixtures — no leaning on real customer data.
- Works unchanged in any worktree (no hardcoded ports).

## Non-Goals

- No CI integration. The trigger is the developer's local update workflow.
- No visual regression / screenshot diffing.
- No `wp-admin` smoke tests (admin fatals surface immediately when `wp plugin update` runs).
- No actual order placement. The WooCommerce smoke test stops at checkout-page render.
- No automated mirroring of updates to production. The developer continues running `wp plugin update` against prod manually.
- No coverage of the ~25 other plugins beyond what the three journeys exercise transitively.
- No retry/flake handling — `retries=0`. Bump later if it becomes annoying.

## Workflow this enables

```shell
# 0. Discover what's pending (no separate slug list to maintain)
docker compose exec wpcli wp plugin list --update=available
docker compose exec wpcli wp theme list --update=available
docker compose exec wpcli wp core check-update

# 1. Update one plugin locally
docker compose exec wpcli wp plugin update <plugin-slug>

# 2. Smoke test
bin/smoke

# 3. If green, mirror to prod
ssh pof 'wp plugin update <plugin-slug>'   # or however remote WP-CLI is invoked

# 4. If red, revert locally and investigate
docker compose exec wpcli wp plugin install <plugin-slug> --version=<previous> --force
```

The same flow applies to Genesis parent-theme updates (`wp theme update genesis`) and WordPress core updates (`wp core update`).

The smoke suite itself is plugin-agnostic — it never sees the slug of the plugin you just updated. It exercises user journeys and tails the PHP error log, so it catches breakage from any update.

We deliberately do **not** wrap steps 1–3 into a single helper. Keeping the update step explicit reinforces the "one at a time" discipline.

## Architecture

### File layout

```
power-of-families/
├── bin/
│   ├── smoke                    # one-shot entry: fixtures → playwright → log check → exit code
│   └── smoke-fixtures           # WP-CLI seeding, idempotent
├── tests/
│   └── smoke/
│       ├── playwright.config.ts # baseURL from $WP_PORT, single project, retries=0
│       ├── fixtures.ts          # shared selectors + credentials constants
│       └── specs/
│           ├── public-pages.spec.ts
│           ├── login-gated.spec.ts
│           └── woo-checkout.spec.ts
└── package.json                 # adds "smoke", "smoke:fixtures", "smoke:install"
```

Smoke tests live under `tests/smoke/`, not the theme's `tests/`, because they're whole-site integration tests rather than theme unit tests.

### Components

**`bin/smoke`** — the entry point. Bash script. Responsibilities:
1. Verify the `wordpress` container is up (`docker compose ps wordpress`); exit with a helpful message if not.
2. Record the PHP error log start position (see *PHP error log tail* below).
3. Run `bin/smoke-fixtures` and capture the emitted `SMOKE_USER_EMAIL`, `SMOKE_GATED_URL`, `SMOKE_PRODUCT_URL`, `SMOKE_LATEST_POST_URL`.
4. Run `npx playwright test --config tests/smoke/playwright.config.ts` with those values in the environment.
5. Slice the PHP error log from the recorded start point and check for new fatals/uncaught exceptions.
6. Exit 0 only if Playwright passed AND the log slice is clean. Otherwise print the relevant failures and exit non-zero.

**`bin/smoke-fixtures`** — idempotent WP-CLI seeding. Each step checks before creating:
1. **Test member user** — email `smoketest@example.com`, role `subscriber` (or whichever role gates members content; resolved during implementation by inspecting an existing gated page). Password read from env `SMOKE_PASSWORD`, defaulting to a documented dev value. `wp user get smoketest@example.com` first; only `wp user create` if it 404s.
2. **Test gated page** — title `Smoke Test Members Page`, slug fixed, body contains the literal string `MEMBERS_ONLY_MARKER` wrapped in whatever members-plugin gate the site uses. The login spec asserts the marker is absent pre-login and present post-login.
3. **Test WooCommerce product** — title `Smoke Test Product`, slug `smoke-test-product`, type `simple`, price `$1`, status `publish`, stock management off so it's always purchasable.
4. Resolves the latest published post's permalink (`wp post list --post_type=post --posts_per_page=1` combined with `wp option get permalink_structure` to produce a fully-formed URL).
5. Prints the resolved identifiers and URLs to stdout in `KEY=value` form so `bin/smoke` can export them as env vars for Playwright: `SMOKE_USER_EMAIL`, `SMOKE_GATED_URL`, `SMOKE_PRODUCT_URL`, `SMOKE_LATEST_POST_URL`.

**`tests/smoke/playwright.config.ts`** — minimal config:
- `baseURL` = `http://localhost:${process.env.WP_PORT ?? 8080}` so worktrees with their own ports work unchanged.
- Single project (Chromium only — additional browsers add time without catching more WP-plugin breakage).
- `retries: 0`.
- Reporter: `list` for terminal output.
- Artifacts: screenshots + trace on failure, written to `tests/smoke/test-results/` (gitignored).

**`tests/smoke/fixtures.ts`** — module-level constants for credentials, selectors, and URL paths. Keeps specs free of magic strings and concentrates the inevitable selector churn in one file.

### The three specs

**`public-pages.spec.ts`** — one test per URL, parallel:
- `/` (homepage)
- `SMOKE_LATEST_POST_URL` (latest published post)
- `/shop/`
- `SMOKE_PRODUCT_URL` (the seeded `smoke-test-product`)

Each test: `goto(url)`, assert response status is 200, assert the page body does *not* contain `Fatal error`, `Parse error`, or the WP user-facing critical-error string `There has been a critical error on this website`. Warnings, notices, and deprecations are deliberately *not* asserted in the body — they're caught (when you want them) by the PHP error log tail's `--strict` mode in the next section, so the default smoke run doesn't fail on the baseline notice noise that a 30-plugin WP install typically carries.

**`login-gated.spec.ts`** — single test:
1. `goto(SMOKE_GATED_URL)` — assert `MEMBERS_ONLY_MARKER` is NOT visible (gating works).
2. Log in via the `/wp-login.php` form using `SMOKE_USER_EMAIL` + `SMOKE_PASSWORD`.
3. `goto(SMOKE_GATED_URL)` again — assert `MEMBERS_ONLY_MARKER` IS visible.

**`woo-checkout.spec.ts`** — single test:
1. `goto(SMOKE_PRODUCT_URL)`, click the Add-to-Cart button.
2. Assert the cart's success notice or count badge (selector lives in `fixtures.ts`).
3. `goto(/checkout/)`, assert a stable checkout-page element (e.g. `#payment` or `.woocommerce-checkout`) renders.
4. Stop. No order placement.

### PHP error log tail

Catches "page returned 200 but threw a warning" — the failure mode that pure HTTP-status checks miss.

**Source:** `docker compose logs wordpress --since=<start-timestamp>`. This is preferred over `wp-content/debug.log` because it works without flipping `WP_DEBUG_LOG` and doesn't require a container restart. Noise from other requests is filtered with grep patterns. `bin/smoke` captures a UTC timestamp before tests start; after tests complete, it pulls the log slice since that timestamp.

**Patterns matched (default):**
- `PHP Fatal`
- `PHP Parse`
- `Uncaught`

**Patterns matched with `--strict`:** the above plus
- `PHP Warning`
- `PHP Notice`
- `Deprecated`

Default omits warnings/notices because a production WordPress site with 30 plugins typically has a baseline of harmless third-party deprecations; making the default run fail on those would train the developer to ignore the signal. `--strict` is for when you specifically want to inspect what a plugin update added to that baseline.

### Worktree compatibility

`bin/smoke` reads `WP_PORT` from the worktree's `.env` (via `docker compose` env loading) or falls back to `8080`. Each worktree's smoke run hits its own stack on its own host port. Fixtures are seeded into whichever DB the worktree is pointing at (Pattern A/B/C from `README.md`), so a worktree using the live-shared DB will see the fixtures in main's data — acceptable since the fixtures use distinctive identifiers.

## Failure handling

On failure the developer gets:

- Playwright `list` reporter output naming each failed spec + the failing assertion.
- Per-failed-test artifacts in `tests/smoke/test-results/<spec-name>/`:
  - PNG screenshot at the point of failure
  - Full Playwright trace (`trace.zip`, openable with `npx playwright show-trace`)
  - The page HTML at failure time
- If the PHP error log slice triggered the failure, the matching log lines are printed inline.
- Non-zero exit code (composes with `&&` if scripted).

Gitignored: `tests/smoke/test-results/`, `tests/smoke/playwright-report/`, Playwright's browser cache.

## Developer ergonomics

**Package.json scripts:**
- `npm run smoke` → `bin/smoke`
- `npm run smoke:fixtures` → `bin/smoke-fixtures` (rarely needed standalone)
- `npm run smoke:install` → `npx playwright install chromium` (one-time browser download)

**README addition:** a "Smoke Testing" section under "Ongoing Development" containing:
- First-run setup (`npm run smoke:install`).
- The discovery commands (`wp plugin list --update=available`, `wp theme list --update=available`, `wp core check-update`) so the developer can see at a glance what needs updating without remembering the WP-CLI syntax.
- The full update loop (discover → update locally → smoke → mirror to prod or revert) documented in the *Workflow this enables* section above.

## Open questions resolved during implementation

These don't block writing the implementation plan but need answers before code lands:

1. **Which members plugin gates the content** — `members`, `groups`, or another. The repo has all three installed. Resolution: inspect an existing gated page on local to identify the active gating mechanism, then have `bin/smoke-fixtures` use the matching shortcode/block/role.
2. **Add-to-Cart success selector** — depends on the active theme's WooCommerce template overrides. Resolution: load the local product page in a browser, observe the post-click DOM, and pin a stable selector in `fixtures.ts`.

## Future work (out of scope here)

- Visual regression via Playwright screenshot comparison, once a baseline is established.
- A `--deep` flag that places a real test order through the WooCommerce "Cash on Delivery" gateway, for catching breakage in the order-creation path.
- `wp-admin` smoke (load dashboard, plugins page, post editor).
- CI integration that gates prod-syncs on a green local smoke run.
- A `bin/update-and-smoke <plugin>` wrapper, *only if* the explicit-step boundary stops being valuable.
