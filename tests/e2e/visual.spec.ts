import { expect, test } from '@playwright/test';
import { login, SCREENS, settle, setTheme } from './helpers';

// Baselines are per platform. Regenerate with: PULSE_E2E_RESET=1 npm run visual -- --update-snapshots
const VIEWPORTS = [
    { name: 'desktop', width: 1360, height: 900 },
    { name: 'phone', width: 390, height: 844 },
] as const;

test.use({ reducedMotion: 'reduce' });

for (const theme of ['light', 'dark'] as const) {
    for (const vp of VIEWPORTS) {
        test.describe(`${theme} ${vp.name}`, () => {
            test.use({ viewport: { width: vp.width, height: vp.height } });

            for (const screen of SCREENS) {
                test(screen.name, async ({ page }) => {
                    await setTheme(page, theme);
                    await login(page, 'planner@pulse.test');
                    await page.goto(screen.path);
                    await settle(page);
                    await expect(page).toHaveScreenshot(`${screen.name}-${theme}-${vp.name}.png`, {
                        maxDiffPixelRatio: 0.02,
                        mask: [page.locator('[data-vr-mask]')],
                    });
                });
            }
        });
    }
}
