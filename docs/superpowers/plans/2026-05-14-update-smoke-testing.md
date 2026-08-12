# Local Update Smoke Testing Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a single `bin/smoke` command that exercises three user journeys (public pages, members login + gated content, WooCommerce add-to-cart through checkout-render) and tails the PHP error log, so the developer can confidently mirror a one-at-a-time local update to production.

**Architecture:** A bash orchestrator (`bin/smoke`) wraps an idempotent WP-CLI fixture seeder (`bin/smoke-fixtures`) and a Playwright suite (`tests/smoke/specs/*.spec.ts`). Playwright runs Chromium against `http://localhost:${WP_PORT}` with `retries=0`. After the suite, the orchestrator pulls `docker compose logs wordpress --since=<start>` and fails the run on `PHP Fatal`/`PHP Parse`/`Uncaught` (or warnings/notices/deprecations under `--strict`).

**Tech Stack:** Playwright (`@playwright/test`, already in devDependencies), Chromium, bash, WP-CLI via the `wpcli` Docker Compose service, Node ≥ 24.

**Spec:** [`docs/superpowers/specs/2026-05-14-update-smoke-testing-design.md`](../specs/2026-05-14-update-smoke-testing-design.md)

---

## Prerequisites

Before starting Task 1, the worktree must be able to run the site. Follow `README.md` "Pattern A — independent DB via stdin import" so this worktree has its own DB seeded from `db-backups/poweroffamilies.dump` and a symlinked `wordpress/` directory. The smoke suite will run against the worktree's own Docker stack on the port set in `.env`. Verify:

```shell
docker compose up -d wordpress
curl -sS -o /dev/null -w '%{http_code}\n' "http://localhost:$(grep ^WP_PORT .env 2>/dev/null | cut -d= -f2 || echo 8080)/"
# Expected: 200 (or 301/302 to the canonical URL — either is fine)
```

If you see anything else, fix it before continuing — every task below assumes a working local site.

---

## File Structure

**Create:**

- `bin/smoke` — orchestrator (bash, executable)
- `bin/smoke-fixtures` — WP-CLI idempotent seeder (bash, executable)
- `tests/smoke/playwright.config.ts`
- `tests/smoke/fixtures.ts` — shared constants
- `tests/smoke/specs/public-pages.spec.ts`
- `tests/smoke/specs/login-gated.spec.ts`
- `tests/smoke/specs/woo-checkout.spec.ts`

**Modify:**

- `package.json` — add `smoke`, `smoke:fixtures`, `smoke:install` scripts
- `.gitignore` — add Playwright artifact dirs
- `README.md` — add "Smoke Testing" section

---

## Task 1: Scaffold tests/smoke/ and install Chromium

**Files:**

- Create: `tests/smoke/playwright.config.ts`
- Create: `tests/smoke/specs/placeholder.spec.ts` (temporary — deleted in Task 7)
- Modify: `package.json`
- Modify: `.gitignore`

- [ ] **Step 1: Add Playwright config**

Create `tests/smoke/playwright.config.ts`:

```ts
import { defineConfig } from '@playwright/test';

const wpPort = process.env.WP_PORT ?? '8080';

export default defineConfig({
	testDir: './specs',
	fullyParallel: true,
	forbidOnly: !!process.env.CI,
	retries: 0,
	workers: undefined,
	reporter: 'list',
	use: {
		baseURL: `http://localhost:${wpPort}`,
		trace: 'retain-on-failure',
		screenshot: 'only-on-failure',
		video: 'off',
	},
	projects: [
		{
			name: 'chromium',
			use: { browserName: 'chromium' },
		},
	],
});
```

- [ ] **Step 2: Add placeholder spec so we can verify the runner**

Create `tests/smoke/specs/placeholder.spec.ts`:

```ts
import { test, expect } from '@playwright/test';

test('placeholder — runner wiring sanity check', () => {
	expect(1 + 1).toBe(2);
});
```

- [ ] **Step 3: Add npm scripts**

Open `package.json` and add these entries inside the existing `"scripts"` object (alongside `"test:docs"`):

```json
"smoke": "bin/smoke",
"smoke:fixtures": "bin/smoke-fixtures",
"smoke:install": "playwright install chromium"
```

- [ ] **Step 4: Add gitignore entries**

Append to `.gitignore`:

```
# Smoke testing artifacts
tests/smoke/test-results/
tests/smoke/playwright-report/
```

- [ ] **Step 5: Install Chromium**

Run: `npm run smoke:install`
Expected: Chromium downloads (a few hundred MB on first run). On subsequent runs, "browser already installed".

- [ ] **Step 6: Run the placeholder spec**

Run: `npx playwright test --config tests/smoke/playwright.config.ts`
Expected: `1 passed`.

- [ ] **Step 7: Commit**

```shell
git add tests/smoke/playwright.config.ts tests/smoke/specs/placeholder.spec.ts package.json .gitignore
git commit -m "Scaffold Playwright smoke runner under tests/smoke/"
```

---

## Task 2: bin/smoke-fixtures — create test user idempotently

**Files:**

- Create: `bin/smoke-fixtures` (executable)

The fixtures script grows incrementally over Tasks 2–5. Each task adds one fixture; running the script after every task should be idempotent (no errors on re-run).

- [ ] **Step 1: Create the script skeleton**

Create `bin/smoke-fixtures` with mode 755:

```bash
#!/usr/bin/env bash
# Idempotently seed test fixtures for bin/smoke. Each fixture checks before
# creating, so this script is safe to re-run.
#
# Emits KEY=value lines on stdout so bin/smoke can `eval` / export them as
# env vars for the Playwright suite.
set -euo pipefail

SMOKE_USER_EMAIL="smoketest@example.com"
SMOKE_USER_LOGIN="smoketest"
SMOKE_PASSWORD="${SMOKE_PASSWORD:-smoke-test-pw-change-me}"

wpcli() {
    docker compose run --rm -T wpcli "$@"
}

ensure_user() {
    if wpcli user get "$SMOKE_USER_EMAIL" --field=ID >/dev/null 2>&1; then
        return 0
    fi
    wpcli user create "$SMOKE_USER_LOGIN" "$SMOKE_USER_EMAIL" \
        --role=subscriber \
        --user_pass="$SMOKE_PASSWORD" \
        --display_name="Smoke Test" >/dev/null
}

ensure_user

echo "SMOKE_USER_EMAIL=$SMOKE_USER_EMAIL"
echo "SMOKE_USER_LOGIN=$SMOKE_USER_LOGIN"
echo "SMOKE_PASSWORD=$SMOKE_PASSWORD"
```

Then: `chmod +x bin/smoke-fixtures`.

- [ ] **Step 2: Run it once**

Run: `bin/smoke-fixtures`
Expected output (last three lines):

```
SMOKE_USER_EMAIL=smoketest@example.com
SMOKE_USER_LOGIN=smoketest
SMOKE_PASSWORD=smoke-test-pw-change-me
```

- [ ] **Step 3: Verify the user exists**

Run: `docker compose run --rm -T wpcli user get smoketest@example.com`
Expected: a table showing the user with role `subscriber`.

- [ ] **Step 4: Run the script again to confirm idempotency**

Run: `bin/smoke-fixtures`
Expected: same output as Step 2, no errors, no duplicate user. Verify with:

```shell
docker compose run --rm -T wpcli user list --search=smoketest --format=count
# Expected: 1
```

- [ ] **Step 5: Commit**

```shell
git add bin/smoke-fixtures
git commit -m "Add smoke-fixtures script with idempotent test user creation"
```

---

## Task 3: bin/smoke-fixtures — add WooCommerce test product

**Files:**

- Modify: `bin/smoke-fixtures`

- [ ] **Step 1: Add the product fixture**

In `bin/smoke-fixtures`, after the `ensure_user` function and before the `ensure_user` call, add:

```bash
SMOKE_PRODUCT_SLUG="smoke-test-product"
SMOKE_PRODUCT_TITLE="Smoke Test Product"

ensure_product() {
    local existing
    existing=$(wpcli post list \
        --post_type=product \
        --name="$SMOKE_PRODUCT_SLUG" \
        --field=ID \
        --format=ids 2>/dev/null | tr -d '[:space:]')
    if [[ -n "$existing" ]]; then
        return 0
    fi
    local id
    id=$(wpcli post create \
        --post_type=product \
        --post_status=publish \
        --post_title="$SMOKE_PRODUCT_TITLE" \
        --post_name="$SMOKE_PRODUCT_SLUG" \
        --porcelain)
    # Make it a simple, purchasable product priced $1 with stock management off.
    wpcli wc product update "$id" \
        --type=simple \
        --regular_price=1 \
        --manage_stock=false \
        --stock_status=instock \
        --user=1 >/dev/null
}
```

Then call `ensure_product` after the `ensure_user` call:

```bash
ensure_user
ensure_product
```

And add a line to the printed output block:

```bash
echo "SMOKE_PRODUCT_SLUG=$SMOKE_PRODUCT_SLUG"
```

- [ ] **Step 2: Run and verify**

Run: `bin/smoke-fixtures`
Expected: clean exit, no errors. Output now includes `SMOKE_PRODUCT_SLUG=smoke-test-product`.

Verify in the DB:

```shell
docker compose run --rm -T wpcli post list --post_type=product --name=smoke-test-product --format=count
# Expected: 1
docker compose run --rm -T wpcli wc product list --slug=smoke-test-product --field=price --user=1
# Expected: "1.00"
```

- [ ] **Step 3: Re-run for idempotency**

Run: `bin/smoke-fixtures` again. Verify count is still 1.

- [ ] **Step 4: Commit**

```shell
git add bin/smoke-fixtures
git commit -m "Add idempotent WooCommerce test product to smoke-fixtures"
```

---

## Task 4: Resolve members-plugin gating mechanism

This is a research task that resolves Open Question #1 from the spec. Output: a documented gating mechanism that Task 5 will use.

**Files:**

- No code changes yet — this is investigation.

- [ ] **Step 1: List active members-related plugins**

Run:

```shell
docker compose run --rm -T wpcli plugin list --status=active --format=csv | grep -iE 'member|group|paid|restrict|woo-membership'
```

Note which plugins are active.

- [ ] **Step 2: Find an existing gated page on the site**

Run:

```shell
docker compose run --rm -T wpcli post list --post_type=page --format=table --posts_per_page=50
```

Identify a page that you know is members-only. Get its ID, then inspect its content:

```shell
docker compose run --rm -T wpcli post get <ID> --field=post_content | head -80
```

Look for shortcodes like `[groups_member]`, `[members_access]`, `[ms-protect-content]`, or block markers like `<!-- wp:groups/restrict -->`. Whatever gates the content there is what we'll use for the smoke fixture.

- [ ] **Step 3: Confirm by toggling logged-in state**

In a browser (logged out), open the gated page — confirm gated content is hidden. Log in as any existing user with the gating role — confirm content appears. This proves the gating mechanism actually works on this install.

- [ ] **Step 4: Determine the role/group the gating uses**

If the plugin gates by role (e.g., the `members` plugin's "logged-in users can see" mode), the role we put on `smoketest` matters. If it gates by group membership (e.g., `groups` plugin), we'll need to add the user to a group in Task 5. Document the answer here in the plan comments, e.g. "Gates by group `Members`, group ID 42".

- [ ] **Step 5: Record findings**

Append a comment block to `bin/smoke-fixtures` documenting the decision:

```bash
# Members-plugin gating: <plugin name> gates content via <mechanism>.
# Test fixture strategy: <how Task 5 will produce gated content visible to smoketest>.
```

Commit:

```shell
git add bin/smoke-fixtures
git commit -m "Document smoke-test members-gating decision"
```

---

## Task 5: bin/smoke-fixtures — add gated members page

**Files:**

- Modify: `bin/smoke-fixtures`

The exact shortcode/block/role added below depends on Task 4's findings. The structure below assumes the common case: a `[shortcode]...[/shortcode]` wrapper that gates by role or membership. Adjust the `gated_content` heredoc to match your plugin's syntax.

- [ ] **Step 1: Add the gated page fixture**

Add to `bin/smoke-fixtures` after `ensure_product`:

```bash
SMOKE_GATED_SLUG="smoke-test-members-page"
SMOKE_GATED_TITLE="Smoke Test Members Page"
SMOKE_GATED_MARKER="MEMBERS_ONLY_MARKER"

ensure_gated_page() {
    local existing
    existing=$(wpcli post list \
        --post_type=page \
        --name="$SMOKE_GATED_SLUG" \
        --field=ID \
        --format=ids 2>/dev/null | tr -d '[:space:]')
    if [[ -n "$existing" ]]; then
        return 0
    fi
    # IMPORTANT: replace the heredoc body with the gating syntax identified
    # in Task 4. Example for the `groups` plugin:
    #   [groups_member group="Members"]MEMBERS_ONLY_MARKER[/groups_member]
    # Example for the `members` plugin:
    #   [members_access role="subscriber"]MEMBERS_ONLY_MARKER[/members_access]
    local gated_content
    gated_content=$(cat <<EOF
Public preamble visible to everyone.

[REPLACE_WITH_GATING_SHORTCODE]
$SMOKE_GATED_MARKER
[/REPLACE_WITH_GATING_SHORTCODE]
EOF
)
    wpcli post create \
        --post_type=page \
        --post_status=publish \
        --post_title="$SMOKE_GATED_TITLE" \
        --post_name="$SMOKE_GATED_SLUG" \
        --post_content="$gated_content" >/dev/null
}
```

Call it after `ensure_product`:

```bash
ensure_user
ensure_product
ensure_gated_page
```

If Task 4 determined the user must be added to a group, add a step inside `ensure_user` (or a new `ensure_user_group` function called after both `ensure_user` and `ensure_gated_page`) that joins the user to the group via WP-CLI — the exact command depends on the plugin.

- [ ] **Step 2: Run the fixtures**

Run: `bin/smoke-fixtures`
Expected: clean exit, no errors.

- [ ] **Step 3: Verify gating works manually**

In a private/incognito browser window, visit `http://localhost:${WP_PORT}/smoke-test-members-page/` (or `/?page_id=<id>` if pretty permalinks aren't on). Expect: `MEMBERS_ONLY_MARKER` is NOT visible.

Log in as `smoketest` / `smoke-test-pw-change-me`. Visit the page again. Expect: `MEMBERS_ONLY_MARKER` IS visible.

If gating doesn't behave as expected, fix the shortcode/role in `bin/smoke-fixtures` before continuing — the Task 8 spec depends on this working.

- [ ] **Step 4: Emit the gated slug**

Add to the printed output block in `bin/smoke-fixtures`:

```bash
echo "SMOKE_GATED_SLUG=$SMOKE_GATED_SLUG"
echo "SMOKE_GATED_MARKER=$SMOKE_GATED_MARKER"
```

- [ ] **Step 5: Re-run for idempotency**

Run: `bin/smoke-fixtures` twice. Verify:

```shell
docker compose run --rm -T wpcli post list --post_type=page --name=smoke-test-members-page --format=count
# Expected: 1
```

- [ ] **Step 6: Commit**

```shell
git add bin/smoke-fixtures
git commit -m "Add idempotent gated members page to smoke-fixtures"
```

---

## Task 6: bin/smoke-fixtures — emit latest-post and product URLs

**Files:**

- Modify: `bin/smoke-fixtures`

- [ ] **Step 1: Add URL resolution**

Add to `bin/smoke-fixtures` (after the `ensure_*` calls, before the `echo` block):

```bash
WP_PORT_LOCAL="${WP_PORT:-8080}"
SITE_URL="http://localhost:${WP_PORT_LOCAL}"

resolve_url() {
    # $1 = post ID
    wpcli post url "$1" 2>/dev/null
}

LATEST_POST_ID=$(wpcli post list \
    --post_type=post \
    --post_status=publish \
    --posts_per_page=1 \
    --field=ID \
    --format=ids 2>/dev/null | tr -d '[:space:]')

if [[ -z "$LATEST_POST_ID" ]]; then
    echo "ERROR: no published posts found — public-pages spec needs one" >&2
    exit 1
fi

PRODUCT_ID=$(wpcli post list \
    --post_type=product \
    --name="$SMOKE_PRODUCT_SLUG" \
    --field=ID \
    --format=ids 2>/dev/null | tr -d '[:space:]')
GATED_ID=$(wpcli post list \
    --post_type=page \
    --name="$SMOKE_GATED_SLUG" \
    --field=ID \
    --format=ids 2>/dev/null | tr -d '[:space:]')

SMOKE_LATEST_POST_URL=$(resolve_url "$LATEST_POST_ID")
SMOKE_PRODUCT_URL=$(resolve_url "$PRODUCT_ID")
SMOKE_GATED_URL=$(resolve_url "$GATED_ID")
```

- [ ] **Step 2: Replace the final echo block**

Replace the trailing `echo` lines with:

```bash
cat <<EOF
SMOKE_USER_EMAIL=$SMOKE_USER_EMAIL
SMOKE_USER_LOGIN=$SMOKE_USER_LOGIN
SMOKE_PASSWORD=$SMOKE_PASSWORD
SMOKE_PRODUCT_SLUG=$SMOKE_PRODUCT_SLUG
SMOKE_PRODUCT_URL=$SMOKE_PRODUCT_URL
SMOKE_GATED_SLUG=$SMOKE_GATED_SLUG
SMOKE_GATED_URL=$SMOKE_GATED_URL
SMOKE_GATED_MARKER=$SMOKE_GATED_MARKER
SMOKE_LATEST_POST_URL=$SMOKE_LATEST_POST_URL
SITE_URL=$SITE_URL
EOF
```

- [ ] **Step 3: Run and inspect**

Run: `bin/smoke-fixtures`

Expected: all `SMOKE_*` and `SITE_URL` keys printed, each with a non-empty value. The three URLs should resolve to fully-formed `http://localhost:${WP_PORT}/...` strings.

If `SMOKE_LATEST_POST_URL` or `SMOKE_PRODUCT_URL` are empty, the corresponding post lookup failed — debug before continuing.

- [ ] **Step 4: Commit**

```shell
git add bin/smoke-fixtures
git commit -m "Resolve and emit smoke-fixture URLs"
```

---

## Task 7: tests/smoke/fixtures.ts — shared spec constants

**Files:**

- Create: `tests/smoke/fixtures.ts`
- Delete: `tests/smoke/specs/placeholder.spec.ts`

- [ ] **Step 1: Create fixtures module**

Create `tests/smoke/fixtures.ts`:

```ts
function required(name: string): string {
	const value = process.env[name];
	if (!value) {
		throw new Error(
			`Missing required env var: ${name}. Did bin/smoke-fixtures run before this spec?`
		);
	}
	return value;
}

export const env = {
	siteUrl: required('SITE_URL'),
	userEmail: required('SMOKE_USER_EMAIL'),
	userLogin: required('SMOKE_USER_LOGIN'),
	password: required('SMOKE_PASSWORD'),
	productUrl: required('SMOKE_PRODUCT_URL'),
	gatedUrl: required('SMOKE_GATED_URL'),
	gatedMarker: required('SMOKE_GATED_MARKER'),
	latestPostUrl: required('SMOKE_LATEST_POST_URL'),
};

// Strings WP renders when something blows up server-side.
export const phpErrorMarkers = [
	'Fatal error',
	'Parse error',
	'There has been a critical error on this website',
];

// Selectors that may need tweaking based on theme overrides. Centralized
// here so spec churn happens in one file.
export const selectors = {
	// WP login form
	loginUsername: '#user_login',
	loginPassword: '#user_pass',
	loginSubmit: '#wp-submit',
	// WooCommerce — verified during Task 9 implementation.
	addToCart: 'button[name="add-to-cart"], .single_add_to_cart_button',
	cartSuccess: '.woocommerce-message, .wc-block-components-notice-banner',
	checkoutForm: '.woocommerce-checkout, form.checkout',
};
```

- [ ] **Step 2: Remove the placeholder spec**

Delete `tests/smoke/specs/placeholder.spec.ts`.

- [ ] **Step 3: Commit**

```shell
git add tests/smoke/fixtures.ts
git rm tests/smoke/specs/placeholder.spec.ts
git commit -m "Add shared fixtures module for smoke specs"
```

---

## Task 8: public-pages.spec.ts

**Files:**

- Create: `tests/smoke/specs/public-pages.spec.ts`

- [ ] **Step 1: Write the spec**

Create `tests/smoke/specs/public-pages.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { env, phpErrorMarkers } from '../fixtures';

const urls: Array<{ name: string; url: string }> = [
	{ name: 'homepage', url: '/' },
	{ name: 'shop', url: '/shop/' },
	{ name: 'latest post', url: env.latestPostUrl },
	{ name: 'product', url: env.productUrl },
];

for (const { name, url } of urls) {
	test(`${name} loads without PHP errors`, async ({ page }) => {
		const response = await page.goto(url);
		expect(response, `no response for ${url}`).not.toBeNull();
		expect(response!.status(), `unexpected status for ${url}`).toBe(200);

		const body = await page.content();
		for (const marker of phpErrorMarkers) {
			expect(body.includes(marker), `Found "${marker}" on ${url}`).toBe(
				false
			);
		}
	});
}
```

- [ ] **Step 2: Run it with the fixture env vars exported**

Run:

```shell
eval "$(bin/smoke-fixtures | sed 's/^/export /')"
npx playwright test --config tests/smoke/playwright.config.ts tests/smoke/specs/public-pages.spec.ts
```

Expected: `4 passed`.

If a URL returns something other than 200 (e.g., 301), update the URL — `/shop` (no trailing slash) vs `/shop/` is a common gotcha, and `/shop/` is preferred to avoid the redirect counting as a non-200.

- [ ] **Step 3: Commit**

```shell
git add tests/smoke/specs/public-pages.spec.ts
git commit -m "Add public-pages smoke spec"
```

---

## Task 9: woo-checkout.spec.ts

**Files:**

- Create: `tests/smoke/specs/woo-checkout.spec.ts`

This task includes a selector-discovery step that resolves Open Question #2 from the spec.

- [ ] **Step 1: Discover the actual Add-to-Cart selector**

In the live site (any incognito window), visit the test product page. Open DevTools, find the Add-to-Cart button. Note:

- Its `name` attribute (commonly `add-to-cart`)
- Its CSS class (commonly `single_add_to_cart_button`)

Click it manually and observe the success indicator that appears. Note its CSS class (commonly `.woocommerce-message`; the WooCommerce blocks-based cart uses `.wc-block-components-notice-banner` instead).

If either differs from the defaults in `tests/smoke/fixtures.ts`, update that file accordingly and commit:

```shell
git add tests/smoke/fixtures.ts
git commit -m "Pin Woo Add-to-Cart selectors in smoke fixtures"
```

- [ ] **Step 2: Write the spec**

Create `tests/smoke/specs/woo-checkout.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { env, phpErrorMarkers, selectors } from '../fixtures';

test('add-to-cart through checkout render', async ({ page }) => {
	await page.goto(env.productUrl);

	await page.locator(selectors.addToCart).first().click();

	await expect(
		page.locator(selectors.cartSuccess).first(),
		'cart success indicator did not appear'
	).toBeVisible({ timeout: 10_000 });

	const checkoutResponse = await page.goto('/checkout/');
	expect(checkoutResponse).not.toBeNull();
	expect(checkoutResponse!.status()).toBe(200);

	await expect(
		page.locator(selectors.checkoutForm).first(),
		'checkout form did not render'
	).toBeVisible({ timeout: 10_000 });

	const body = await page.content();
	for (const marker of phpErrorMarkers) {
		expect(body.includes(marker), `Found "${marker}" on /checkout/`).toBe(
			false
		);
	}
});
```

- [ ] **Step 3: Run it**

Run:

```shell
eval "$(bin/smoke-fixtures | sed 's/^/export /')"
npx playwright test --config tests/smoke/playwright.config.ts tests/smoke/specs/woo-checkout.spec.ts
```

Expected: `1 passed`. If it fails on the cart-success assertion, inspect `tests/smoke/test-results/` (screenshot + trace) and revisit selectors. If it fails on checkout-form, your install may use the WooCommerce blocks checkout — update `selectors.checkoutForm` to include a blocks-checkout selector like `.wp-block-woocommerce-checkout`.

- [ ] **Step 4: Commit**

```shell
git add tests/smoke/specs/woo-checkout.spec.ts
git commit -m "Add WooCommerce add-to-cart smoke spec"
```

---

## Task 10: login-gated.spec.ts

**Files:**

- Create: `tests/smoke/specs/login-gated.spec.ts`

- [ ] **Step 1: Write the spec**

Create `tests/smoke/specs/login-gated.spec.ts`:

```ts
import { test, expect } from '@playwright/test';
import { env, selectors } from '../fixtures';

test('gated members page hides marker when logged out, reveals when logged in', async ({
	page,
}) => {
	// 1. Logged-out: marker must not appear.
	await page.goto(env.gatedUrl);
	await expect(
		page.locator('body'),
		'gated content leaked to logged-out user'
	).not.toContainText(env.gatedMarker);

	// 2. Log in via wp-login.php.
	await page.goto('/wp-login.php');
	await page.locator(selectors.loginUsername).fill(env.userLogin);
	await page.locator(selectors.loginPassword).fill(env.password);
	await Promise.all([
		page.waitForNavigation(),
		page.locator(selectors.loginSubmit).click(),
	]);

	// wp-login.php redirects to /wp-admin/ on success. If we landed elsewhere
	// (e.g., back on the login page with an error), bail with a clear message.
	expect(
		page.url(),
		`login redirect went somewhere unexpected: ${page.url()}`
	).toMatch(/wp-admin|profile|members/);

	// 3. Logged-in: marker must appear.
	await page.goto(env.gatedUrl);
	await expect(
		page.locator('body'),
		'gated content not visible to logged-in member'
	).toContainText(env.gatedMarker);
});
```

- [ ] **Step 2: Run it**

Run:

```shell
eval "$(bin/smoke-fixtures | sed 's/^/export /')"
npx playwright test --config tests/smoke/playwright.config.ts tests/smoke/specs/login-gated.spec.ts
```

Expected: `1 passed`.

If logged-in assertion fails, the gating shortcode in `bin/smoke-fixtures` doesn't recognize the role/group you assigned to `smoketest`. Revisit Task 4 / Task 5.

- [ ] **Step 3: Commit**

```shell
git add tests/smoke/specs/login-gated.spec.ts
git commit -m "Add members login + gated content smoke spec"
```

---

## Task 11: bin/smoke — orchestrator

**Files:**

- Create: `bin/smoke` (executable)

- [ ] **Step 1: Write the orchestrator**

Create `bin/smoke` with mode 755:

```bash
#!/usr/bin/env bash
# One-shot smoke test for after a plugin / theme / core update.
#
# Pipeline:
#   1. Confirm the wordpress container is up.
#   2. Record the docker-logs cutoff timestamp.
#   3. Seed fixtures (idempotent) and export their identifiers.
#   4. Run Playwright.
#   5. Slice the wordpress container logs since the cutoff and check for
#      PHP fatals / parse errors / uncaught exceptions. With --strict, also
#      fail on warnings / notices / deprecations.
#
# Exits 0 only if Playwright passed AND the log slice is clean.
set -euo pipefail

ROOT_DIR=$(cd "$(dirname "$0")/.." && pwd)
cd "$ROOT_DIR"

STRICT=0
for arg in "$@"; do
    case "$arg" in
        --strict) STRICT=1 ;;
        -h|--help)
            cat <<EOF
Usage: bin/smoke [--strict]

  --strict   also fail on PHP Warning / PHP Notice / Deprecated
EOF
            exit 0
            ;;
        *)
            echo "Unknown argument: $arg" >&2
            exit 2
            ;;
    esac
done

# 1. Container check.
if ! docker compose ps --status=running --services 2>/dev/null | grep -qx wordpress; then
    cat >&2 <<EOF
wordpress container is not running. Bring it up first:

    docker compose up -d wordpress

EOF
    exit 1
fi

# 2. Cutoff timestamp (RFC3339, UTC).
SMOKE_LOG_SINCE=$(date -u +"%Y-%m-%dT%H:%M:%SZ")

# 3. Seed fixtures and export their KEY=value output as env vars.
echo "==> Seeding smoke fixtures…" >&2
FIXTURES_OUT=$(bin/smoke-fixtures)
while IFS= read -r line; do
    [[ -z "$line" || "$line" != *=* ]] && continue
    export "$line"
done <<< "$FIXTURES_OUT"

# 4. Run Playwright.
echo "==> Running Playwright smoke suite…" >&2
PLAYWRIGHT_EXIT=0
npx playwright test --config tests/smoke/playwright.config.ts || PLAYWRIGHT_EXIT=$?

# 5. Log slice.
echo "==> Checking PHP error log since $SMOKE_LOG_SINCE…" >&2
LOG_SLICE=$(docker compose logs wordpress --since="$SMOKE_LOG_SINCE" --no-color 2>&1 || true)

if [[ "$STRICT" -eq 1 ]]; then
    PATTERN='PHP Fatal|PHP Parse|Uncaught|PHP Warning|PHP Notice|Deprecated'
else
    PATTERN='PHP Fatal|PHP Parse|Uncaught'
fi

LOG_HITS=$(echo "$LOG_SLICE" | grep -E "$PATTERN" || true)
LOG_EXIT=0
if [[ -n "$LOG_HITS" ]]; then
    LOG_EXIT=1
    echo "==> PHP error log slice contained errors:" >&2
    echo "$LOG_HITS" >&2
fi

if [[ "$PLAYWRIGHT_EXIT" -ne 0 || "$LOG_EXIT" -ne 0 ]]; then
    echo "==> SMOKE FAILED (playwright=$PLAYWRIGHT_EXIT, log=$LOG_EXIT)" >&2
    exit 1
fi

echo "==> SMOKE PASSED" >&2
```

Then: `chmod +x bin/smoke`.

- [ ] **Step 2: Run it end-to-end**

Run: `bin/smoke`
Expected:

- `==> Seeding smoke fixtures…`
- `==> Running Playwright smoke suite…` → `6 passed` (4 public-pages + 1 login-gated + 1 woo-checkout)
- `==> Checking PHP error log since …`
- `==> SMOKE PASSED`
- Exit code 0.

Verify exit code: `echo $?` → `0`.

- [ ] **Step 3: Confirm strict mode also passes (or doesn't, and that's informative)**

Run: `bin/smoke --strict || true`

If this passes, your install is clean. If it fails with notices/deprecations, that's the existing baseline — the failure output shows what's currently noisy, and re-running `bin/smoke --strict` after a future update will surface anything _new_. Don't fix the baseline noise; it's expected.

- [ ] **Step 4: Confirm failure path works**

Temporarily break a spec to confirm a failure produces useful output. In `tests/smoke/specs/public-pages.spec.ts`, change `/` to `/this-definitely-does-not-exist-9876/` and run `bin/smoke`. Expected: a failure naming the test, a screenshot/trace in `tests/smoke/test-results/`, exit code 1.

Revert the change:

```shell
git checkout tests/smoke/specs/public-pages.spec.ts
```

- [ ] **Step 5: Commit**

```shell
git add bin/smoke
git commit -m "Add bin/smoke orchestrator with PHP error log tail"
```

---

## Task 12: README — Smoke Testing section

**Files:**

- Modify: `README.md`

- [ ] **Step 1: Add the section**

Open `README.md` and insert this as a new section under `## Ongoing Development`, after the existing `### Build Everything` block and before `## Miscellaneous`:

````markdown
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
ssh pof 'wp plugin update <slug>'   # or however remote WP-CLI is invoked

# 4. If red, revert locally
docker compose run --rm wpcli plugin install <slug> --version=<previous> --force
```

The smoke suite is plugin-agnostic — it never sees which plugin was just
updated. Run it after any single update. Use `npm run smoke -- --strict`
to also fail on PHP warnings / notices / deprecated calls.

Test fixtures (a `smoketest@example.com` user, a `Smoke Test Product`,
and a gated members page) are seeded idempotently by `bin/smoke-fixtures`
on every run; no manual setup needed beyond `npm run smoke:install`.
````

- [ ] **Step 2: Verify the rendered Markdown**

Skim the section in a Markdown preview (VS Code's preview, GitHub's web view of the worktree branch, or `glow`). Confirm:

- Code blocks are fenced correctly.
- The discovery + loop commands are easy to scan.
- The first-run setup is unambiguous.

- [ ] **Step 3: Commit**

```shell
git add README.md
git commit -m "Document smoke testing workflow in README"
```

---

## Task 13: Final end-to-end verification

**Files:**

- No code changes.

- [ ] **Step 1: Clean slate run**

From a freshly-running stack:

```shell
docker compose up -d wordpress
npm run smoke
```

Expected: `==> SMOKE PASSED`, exit code 0.

- [ ] **Step 2: Confirm fixtures aren't duplicated**

```shell
docker compose run --rm -T wpcli user list --search=smoketest --format=count
docker compose run --rm -T wpcli post list --post_type=product --name=smoke-test-product --format=count
docker compose run --rm -T wpcli post list --post_type=page --name=smoke-test-members-page --format=count
```

Each: `1`.

- [ ] **Step 3: Simulate an update + smoke cycle**

Pick a low-risk plugin currently showing an available update (or use `--dry-run` if WP-CLI supports it for the chosen subcommand). Run:

```shell
docker compose run --rm wpcli plugin list --update=available
docker compose run --rm wpcli plugin update <slug>
npm run smoke
```

Expected: smoke green, you'd be confident mirroring that update to prod.

- [ ] **Step 4: Confirm artifacts are gitignored**

```shell
git status
```

Expected: `tests/smoke/test-results/` and `tests/smoke/playwright-report/` (if they exist after the runs) do NOT appear as untracked files.

- [ ] **Step 5: No commit needed unless something needed fixing**

If you found and fixed issues during this task, commit them with a single `Final verification fixes` commit. Otherwise this task ends with no new commit.

---

## Done criteria

- `npm run smoke` produces `==> SMOKE PASSED` on a freshly-running stack against the current set of installed plugins.
- A deliberately broken spec produces clear failure output with a screenshot + trace in `tests/smoke/test-results/`.
- `npm run smoke -- --strict` either passes or, on failure, shows the existing baseline noise (this is informative, not actionable).
- Running `bin/smoke-fixtures` twice in a row leaves the DB in the same state (idempotent).
- README documents discovery + update loop, and any developer can run `npm run smoke:install && npm run smoke` from a fresh worktree checkout (assuming Pattern A DB setup) without further instructions.
