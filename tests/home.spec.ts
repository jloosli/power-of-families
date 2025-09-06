import { test, expect } from '@playwright/test';

test.describe('WordPress Site', () => {
	test('home page loads', async ({ page }) => {
		await page.goto('/');

		// Wait for the main content area to be visible
		await expect(page.locator('main')).toBeVisible();

		// Verify the site title is present
		await expect(page.locator('header')).toContainText('Power of Families');
	});
});
