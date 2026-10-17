import { mount } from '@vue/test-utils';
import { beforeEach, describe, expect, it, vi } from 'vitest';
import { defineComponent, h } from 'vue';
import { resetWeights } from '../lib/useWeights';
import { MunicipalityRow } from '../types/rows';

vi.mock('@inertiajs/vue3', () => ({
    Head: defineComponent({ render: () => null }),
    Link: defineComponent({ props: ['href'], render() { return h('a', { href: this.href }, this.$slots.default?.()); } }),
    usePage: () => ({ props: { dataset: { source: 'synthetic', cycle: 2026, priorCycle: 2025 }, auth: { user: { name: 'T', role: 'planner' } } } }),
}));
vi.mock('@/Layouts/AppLayout.vue', () => ({ default: defineComponent({ render() { return h('div', this.$slots.default?.()); } }) }));
vi.mock('@/Components/MunicipalityMap.vue', () => ({ default: defineComponent({ render: () => h('div', { 'data-test': 'map' }) }) }));

const { default: MapPage } = await import('../Pages/Map.vue');
const { default: PipelinePage } = await import('../Pages/Pipeline.vue');

const row = (i: number, over: Partial<MunicipalityRow> = {}): MunicipalityRow => ({
    psgc: `P${i}`, name: `Town ${i}`, province: i % 2 ? 'Agusan del Norte' : 'Surigao del Sur', lat: 8, lon: 125, status: 'ok',
    rate_per_1000: 10 + i, attendance: 0.9 - i * 0.05, change: -0.05 * i, applicants: 30, examinees: 25, qualifiers: null,
    grade6_pop: 1000, prior_applicants: 35, lisa_cluster: 'ns', ...over,
});
const rows = [row(1), row(2), row(3)];
const global = { mocks: { route: (name: string) => `/${name}` } };
const metrics = [{ key: 'rate', label: 'Rate' }, { key: 'cluster', label: 'Clusters' }];

beforeEach(() => {
    sessionStorage.clear();
    resetWeights();
});

describe('Map page', () => {
    it('lets keyboard users pick a municipality from a labelled list', async () => {
        const page = mount(MapPage, { props: { rows, metrics }, global });
        const select = page.get('#map-municipality');
        expect(page.get('label[for="map-municipality"]').text()).toMatch(/municipality/i);
        await select.setValue('P3');
        expect(page.get('aside').text()).toContain('Town 3');
    });

    it('tells the user when the dataset has no Grade 6 population', () => {
        const noDenominator = rows.map((r) => ({ ...r, rate_per_1000: null }));
        const page = mount(MapPage, { props: { rows: noDenominator, metrics }, global });
        expect(page.text()).toMatch(/no Grade 6 population/i);
    });
});

describe('Pipeline page', () => {
    const props = { regionMedians: { rate: null, attendance: null }, hidden: { suppressed: 0, missing: 0 } };

    it('explains an empty chart instead of drawing nonsense', () => {
        const noDenominator = rows.map((r) => ({ ...r, rate_per_1000: null }));
        const page = mount(PipelinePage, { props: { rows: noDenominator, ...props }, global });
        expect(page.findAll('circle')).toHaveLength(0);
        expect(page.text()).toMatch(/nothing to chart/i);
        expect(page.text()).toMatch(/no Grade 6 population/i);
        expect(page.html()).not.toContain('Infinity');
        expect(page.html()).not.toContain('NaN');
    });

    it('draws one point per municipality when the data is complete', () => {
        const page = mount(PipelinePage, { props: { rows, regionMedians: { rate: 12, attendance: 0.8 }, hidden: { suppressed: 0, missing: 0 } }, global });
        expect(page.findAll('circle')).toHaveLength(3);
    });
});
