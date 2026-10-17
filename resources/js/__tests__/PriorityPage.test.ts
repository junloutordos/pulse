import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { resetWeights } from '../lib/useWeights';
import { MunicipalityRow } from '../types/rows';

vi.mock('@inertiajs/vue3', () => ({
    Head: defineComponent({ render: () => null }),
    Link: defineComponent({ props: ['href'], render() { return h('a', { href: this.href }, this.$slots.default?.()); } }),
    usePage: () => ({ props: { dataset: { source: 'synthetic', cycle: 2026 }, auth: { user: { name: 'T', role: 'planner' } } } }),
}));
vi.mock('@/Layouts/AppLayout.vue', () => ({
    default: defineComponent({ render() { return h('div', this.$slots.default?.()); } }),
}));

const { default: Priority } = await import('../Pages/Priority.vue');

const row = (i: number, over: Partial<MunicipalityRow> = {}): MunicipalityRow => ({
    psgc: `P${i}`, name: `Town ${i}`, province: 'Agusan del Norte', lat: 8, lon: 125, status: 'ok',
    rate_per_1000: 10 + i, attendance: 0.9 - i * 0.05, change: -0.05 * i, applicants: 30, examinees: 25, qualifiers: null,
    grade6_pop: 1000, prior_applicants: 35, lisa_cluster: 'ns', ...over,
});
const rows = [row(1), row(2), row(3), row(4, { status: 'low' }), row(5, { status: 'suppressed', rate_per_1000: null, attendance: null, change: null, applicants: null })];

const mountPage = () => mount(Priority, { props: { rows, canPlan: true }, global: { mocks: { route: (name: string) => `/${name}` } } });

beforeEach(() => {
    sessionStorage.clear();
    resetWeights();
});

describe('Priority page', () => {
    it('lists unranked municipalities with the reason they are not ranked', () => {
        const page = mountPage();
        const text = page.text();
        expect(text).toContain('Not ranked (2)');
        expect(text).toContain('Town 4');
        expect(text).toContain('fewer than 15 applicants');
        expect(text).toContain('Town 5');
        expect(text).toContain('applicant count suppressed');
    });

    it('never shows an unranked municipality in the ranked table', () => {
        const page = mountPage();
        const ranked = page.findAll('tbody tr').map((tr) => tr.text());
        expect(ranked).toHaveLength(3);
        expect(ranked.join(' ')).not.toContain('Town 4');
    });

    it('all zero weights keeps previous weights and shows a message', async () => {
        const page = mountPage();
        for (const key of ['attendance', 'decline', 'reach']) {
            const input = page.get(`#w-${key}`);
            await input.setValue('0');
        }
        expect(page.get('[role="alert"]').text()).toMatch(/at least one weight/i);
        expect((page.get('#w-reach').element as HTMLInputElement).value).toBe('33');
    });

    it('applies a preset', async () => {
        const page = mountPage();
        await page.findAll('button').find((b) => b.text() === 'Reach first')!.trigger('click');
        expect((page.get('#w-reach').element as HTMLInputElement).value).toBe('60');
    });
});
