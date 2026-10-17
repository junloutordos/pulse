import { defineConfig, devices } from '@playwright/test';

// Needs the stack running: docker compose up -d, a synthetic version in data/versions, and `npm run build`.
export default defineConfig({
    testDir: 'tests/e2e',
    globalSetup: './tests/e2e/global-setup.ts',
    timeout: 120_000,
    workers: 1,
    reporter: 'list',
    use: {
        baseURL: process.env.PULSE_URL ?? 'http://localhost:8000',
        trace: 'retain-on-failure',
        screenshot: 'only-on-failure',
        viewport: { width: 1360, height: 900 },
    },
    projects: [{ name: 'chromium', use: { ...devices['Desktop Chrome'], viewport: { width: 1360, height: 900 } } }],
});
