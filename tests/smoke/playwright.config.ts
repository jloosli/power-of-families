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
