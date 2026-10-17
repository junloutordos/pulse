import { expect, Page } from '@playwright/test';

export async function login(page: Page, email: string) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).toHaveURL(/dashboard/);
}

export async function setTheme(page: Page, theme: 'light' | 'dark') {
    await page.addInitScript((t) => localStorage.setItem('pulse-theme', t), theme);
}

export const SCREENS = [
    { name: 'start', path: '/dashboard' },
    { name: 'trust', path: '/trust' },
    { name: 'map', path: '/map' },
    { name: 'pipeline', path: '/pipeline' },
    { name: 'priority', path: '/priority' },
    { name: 'planner', path: '/planner' },
    { name: 'brief', path: '/brief' },
] as const;

/** The map sweeps in and the pages rise in; with reduced motion on, the first paint is already the final one. */
export async function settle(page: Page) {
    await page.waitForLoadState('networkidle');
    if (page.url().includes('/map') || page.url().includes('/planner')) await page.locator('.maplibregl-canvas').waitFor();
    await page.waitForTimeout(page.url().includes('/map') || page.url().includes('/planner') ? 1500 : 400);
}
