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
