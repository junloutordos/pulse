import AxeBuilder from '@axe-core/playwright';
import { expect, test } from '@playwright/test';
import { login, SCREENS, settle, setTheme } from './helpers';

for (const theme of ['light', 'dark'] as const) {
    test.describe(`accessibility, ${theme} theme`, () => {
        test.beforeEach(async ({ page }) => {
            await setTheme(page, theme);
            await login(page, 'planner@pulse.test');
        });

        for (const screen of SCREENS) {
            test(`${screen.name} has no serious or critical axe violations`, async ({ page }) => {
                await page.goto(screen.path);
                await settle(page);
                const results = await new AxeBuilder({ page }).withTags(['wcag2a', 'wcag2aa', 'wcag21a', 'wcag21aa']).analyze();
                const serious = results.violations.filter((v) => v.impact === 'serious' || v.impact === 'critical');
                expect(serious.map((v) => `${v.id}: ${v.nodes.slice(0, 3).map((n) => n.target.join(' ')).join(' | ')}`)).toEqual([]);
            });
        }
    });
}

test('the skip link is the first stop and moves focus to the content', async ({ page }) => {
    await login(page, 'planner@pulse.test');
    await page.goto('/priority');
    await page.keyboard.press('Tab');
    await expect(page.getByRole('link', { name: 'Skip to content' })).toBeFocused();
    await page.keyboard.press('Enter');
    await expect(page.locator('#main')).toBeFocused();
});

test('moving to another screen puts focus on the new content', async ({ page }) => {
    await login(page, 'planner@pulse.test');
    await page.goto('/trust');
    await page.getByRole('navigation', { name: 'Primary' }).getByRole('link', { name: /Priority list$/ }).click();
    await expect(page).toHaveURL(/priority/);
    await expect(page.locator('#main')).toBeFocused();
});

test('reduced motion turns the page animation off', async ({ page }) => {
    await page.emulateMedia({ reducedMotion: 'reduce' });
    await login(page, 'planner@pulse.test');
    await page.goto('/trust');
    const seconds = await page.locator('.page-enter').first().evaluate((el) => parseFloat(getComputedStyle(el).animationDuration));
    expect(seconds).toBeLessThan(0.001);
});

test('the jump bar opens with Ctrl+K, finds a municipality and goes to it', async ({ page }) => {
    await login(page, 'planner@pulse.test');
    await page.goto('/trust');
    await page.keyboard.press('Control+K');
    await expect(page.getByRole('dialog', { name: /Jump to/ })).toBeVisible();
    await page.keyboard.type('butuan');
    await expect(page.getByRole('option').first()).toContainText('City of Butuan');
    await page.keyboard.press('Enter');
    await expect(page).toHaveURL(/\/map\?.*psgc=1630400000/);
});
