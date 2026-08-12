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
