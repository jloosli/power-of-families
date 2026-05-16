import { test, expect } from '@playwright/test';
import { env, selectors } from '../fixtures';

test('gated members page is hidden when logged out, visible when logged in', async ({
	page,
}) => {
	// 1. Logged-out: groups plugin returns a hard 404 for gated pages.
	const anonResponse = await page.goto(env.gatedUrl);
	expect(anonResponse).not.toBeNull();
	expect(
		anonResponse!.status(),
		'gated page should 404 for logged-out users'
	).toBe(404);

	// 2. Log in via wp-login.php.
	await page.goto('/wp-login.php');
	await page.locator(selectors.loginUsername).fill(env.userLogin);
	await page.locator(selectors.loginPassword).fill(env.password);
	await Promise.all([
		page.waitForNavigation(),
		page.locator(selectors.loginSubmit).click(),
	]);

	// wp-login.php redirects to /wp-admin/ on success. Bail with a clear
	// message if we landed back on the login page (indicating bad creds).
	expect(
		page.url(),
		`login redirect went somewhere unexpected: ${page.url()}`
	).toMatch(/wp-admin|profile|members|my-account/);

	// 3. Logged-in: marker must appear in the gated page body.
	const memberResponse = await page.goto(env.gatedUrl);
	expect(memberResponse!.status()).toBe(200);
	await expect(
		page.locator('body'),
		'gated content not visible to logged-in member'
	).toContainText(env.gatedMarker);
});
