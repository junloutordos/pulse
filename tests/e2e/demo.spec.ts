import { expect, Page, test } from '@playwright/test';

async function login(page: Page, email: string) {
    await page.goto('/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill('password');
    await page.getByRole('button', { name: /log in/i }).click();
    await expect(page).toHaveURL(/dashboard/);
}

const nav = (page: Page, name: string) => page.getByRole('navigation', { name: 'Primary' }).getByRole('link', { name, exact: true });

const topFive = (page: Page) => page.locator('tbody tr td:nth-child(2) a').evaluateAll((els) => els.slice(0, 5).map((e) => e.textContent!.trim()));

test('source-to-decision demo path', async ({ page, request }) => {
    const problems: string[] = [];
    page.on('pageerror', (e) => problems.push(`pageerror: ${e.message}`));
    page.on('console', (m) => m.type() === 'error' && problems.push(`console: ${m.text()}`));

    // 1. Planner signs in and sees the synthetic-data banner on every screen.
    await login(page, 'planner@pulse.test');
    await expect(page.getByRole('status').filter({ hasText: 'Synthetic data' })).toBeVisible();

    // 2. Data trust shows what needed attention before anything is mapped.
    await nav(page, 'Data trust').click();
    await expect(page.getByRole('heading', { name: 'Can we trust this dataset?' })).toBeVisible();
    await expect(page.getByText('Every total reconciles with the custodian.')).toBeVisible();
    await expect(page.getByRole('row', { name: /Tubajon.*Missing/ })).toBeVisible();
    await expect(page.getByRole('row', { name: /Barobo.*Suppressed/ })).toBeVisible();

    // 3. The map draws municipalities and switches metric.
    await nav(page, 'Map').click();
    await expect(page.locator('.maplibregl-canvas')).toBeVisible();
    await page.getByRole('button', { name: 'Exam attendance' }).click();
    await expect(page.getByRole('button', { name: 'Exam attendance' })).toHaveAttribute('aria-pressed', 'true');
    await expect(page.getByText('Pipeline stages, share of applicants')).toBeVisible();

    // 4. The pipeline scatter separates low applications from low attendance.
    await nav(page, 'Pipeline').click();
    await expect(page.getByRole('img', { name: /Scatter of applications/ })).toBeVisible();

    // 5. Priority: weights change the order, and unsafe weights are refused.
    await nav(page, 'Priority list').click();
    await page.getByRole('button', { name: 'Reach first' }).click();
    const reachFirst = await topFive(page);
    await page.getByRole('button', { name: 'Attendance first' }).click();
    const attendanceFirst = await topFive(page);
    expect(reachFirst).not.toEqual(attendanceFirst);
    await expect(page.getByText(/applicant count suppressed/)).toBeVisible();
    for (const key of ['reach', 'attendance', 'decline']) await page.locator(`#w-${key}`).fill('0');
    await expect(page.getByRole('alert')).toContainText(/at least one weight/i);
    await page.getByRole('button', { name: 'Balanced' }).click();
    await expect(page.getByRole('alert')).toHaveCount(0);

    // 6. Planner: five slots, accept the first suggestion.
    await nav(page, 'Planner').click();
    await page.locator('#slots').fill('5');
    await expect(page.locator('ol > li')).toHaveCount(5);
    const firstHref = await page.locator('ol > li').first().getByRole('link').first().getAttribute('href');
    const psgc = new URL(firstHref!, 'http://x').searchParams.get('psgc')!;
    await page.getByRole('button', { name: 'Accept' }).first().click();
    await expect(page.getByText('1 accepted · 1 in register')).toBeVisible();

    // 7. Brief: watermark and provenance in the page, a real PDF, and the register entry.
    await nav(page, 'Brief').click();
    const brief = page.frameLocator('iframe[title="Evidence brief preview"]');
    await expect(brief.getByText('SYNTHETIC DATA')).toBeVisible();
    await expect(brief.getByText(/Dataset \d{4}\.\d{2}\.\d{2}(-\d{4})?-syn/)).toBeVisible();
    await expect(page.getByRole('cell', { name: 'Planned', exact: true })).toBeVisible();
    await page.getByRole('button', { name: 'Mark completed' }).click();
    await expect(page.getByText(/Completed \d{4}-\d{2}-\d{2}/)).toBeVisible();
    const cookies = await page.context().cookies();
    const pdf = await request.get(`/brief/${psgc}/pdf`, { headers: { cookie: cookies.map((c) => `${c.name}=${c.value}`).join('; ') } });
    expect(pdf.status()).toBe(200);
    expect(pdf.headers()['content-type']).toContain('application/pdf');

    // 8. A viewer can read everything but cannot record decisions.
    await page.getByRole('button', { name: 'Log out' }).click();
    await login(page, 'viewer@pulse.test');
    await page.goto('/planner');
    await expect(page.getByText('You can view the shortlist. Planners record decisions.')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Accept' })).toHaveCount(0);
    await page.goto('/brief');
    await expect(page.getByRole('button', { name: 'Mark completed' })).toHaveCount(0);

    expect(problems).toEqual([]);
});
