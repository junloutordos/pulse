<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import DataNotices from '@/Components/DataNotices.vue';
import MapLegend from '@/Components/MapLegend.vue';
import MunicipalityDetail from '@/Components/MunicipalityDetail.vue';
import MunicipalityMap, { MapTip } from '@/Components/MunicipalityMap.vue';
import SelectField from '@/Components/SelectField.vue';
import Segmented from '@/Components/Segmented.vue';
import { binOf, legendLabels, quantileBreaks } from '@/lib/breaks';
import { fixed, pct, signedPct } from '@/lib/format';
import { useThemeColors } from '@/lib/themeColors';
import { useWeights } from '@/lib/useWeights';
import { PageProps } from '@/types';
import { MunicipalityRow } from '@/types/rows';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

// The map fills the screen under the header, so this page asks the persistent layout for its full-bleed variant.
defineOptions({ layout: (h: typeof import('vue').h, page: unknown) => h(AppLayout, { bleed: true }, () => page) });

const props = defineProps<{ rows: MunicipalityRow[]; metrics: { key: string; label: string }[] }>();
const dataset = computed(() => usePage<PageProps>().props.dataset!);
const colors = useThemeColors();
const { scored } = useWeights(computed(() => props.rows));

const rank = computed(() => new Map(scored.value.map((s) => [s.psgc, s])));
const rankedCount = computed(() => scored.value.filter((s) => s.rank !== null).length);

const METRIC_HELP: Record<string, string> = {
    rate: 'How many apply, adjusted for the number of Grade 6 learners',
    attendance: 'Of those who applied, how many sat the exam',
    change: 'How applications moved since the prior cycle',
    score: 'The combined priority score at your current weights',
    cluster: 'Where high or low participation groups together',
};
const wide = typeof window.matchMedia === 'function' && window.matchMedia('(min-width: 1024px)').matches;
const padding = wide ? { top: 32, bottom: 32, left: 380, right: 420 } : { top: 24, bottom: 24, left: 24, right: 24 };
const metric = ref<string>('rate');
const wanted = new URLSearchParams(window.location.search).get('psgc');
const first = props.rows.find((r) => r.psgc === wanted) ?? props.rows.find((r) => rank.value.get(r.psgc)?.rank === 1) ?? props.rows[0];
const selected = ref<string>(first.psgc);

const SHAPE_KEY = 'pulse-map-shape';
const readShape = (): 'tile' | 'boundary' => {
    try {
        return localStorage.getItem(SHAPE_KEY) === 'tile' ? 'tile' : 'boundary';
    } catch {
        return 'boundary';
    }
};
const shape = ref<'tile' | 'boundary'>(readShape());
watch(shape, (v) => {
    try {
        localStorage.setItem(SHAPE_KEY, v);
    } catch {
        // The choice then lasts for this page view only.
    }
});

const FORMAT: Record<string, (v: number) => string> = {
    rate: (v) => fixed(v, 1),
    attendance: (v) => pct(v),
    change: (v) => signedPct(v),
    score: (v) => fixed(v, 0),
};

const value = (r: MunicipalityRow): number | null =>
    ({ rate: r.rate_per_1000, attendance: r.attendance, change: r.change, score: rank.value.get(r.psgc)?.score ?? null, cluster: null })[metric.value] ?? null;

const breaks = computed(() => quantileBreaks(props.rows.map(value), 5));
const isCluster = computed(() => metric.value === 'cluster');
const CLUSTER_FILL = computed<Record<string, string>>(() => ({ HH: colors.value.q4, LL: colors.value.crit, HL: colors.value.warn, LH: colors.value.warn, ns: colors.value.land }));

const fills = computed<Record<string, string | null>>(() =>
    Object.fromEntries(
        props.rows.map((r) => {
            if (isCluster.value) return [r.psgc, r.lisa_cluster ? CLUSTER_FILL.value[r.lisa_cluster] : null];
            const bin = binOf(value(r), breaks.value);
            return [r.psgc, bin === null ? null : colors.value[`q${bin}` as 'q0']];
        }),
    ),
);

const tips = computed<Record<string, MapTip>>(() =>
    Object.fromEntries(
        props.rows.map((r) => {
            const v = value(r);
            const shown = isCluster.value ? (r.lisa_cluster === 'ns' || !r.lisa_cluster ? 'No clear pattern' : { HH: 'Hot spot', LL: 'Cold spot', HL: 'Outlier', LH: 'Outlier' }[r.lisa_cluster]!) : v === null ? 'No value' : FORMAT[metric.value](v);
            const rk = rank.value.get(r.psgc)?.rank;
            return [r.psgc, { title: `${r.name}, ${r.province}`, value: shown!, detail: rk ? `Priority #${rk} of ${rankedCount.value}` : 'Not ranked' }];
        }),
    ),
);

const legend = computed(() => {
    if (isCluster.value) {
        const n = (c: string[]) => props.rows.filter((r) => r.lisa_cluster && c.includes(r.lisa_cluster)).length;
        return [
            { label: 'Hot spot', color: colors.value.q4, count: n(['HH']) },
            { label: 'Cold spot', color: colors.value.crit, count: n(['LL']) },
            { label: 'Outlier', color: colors.value.warn, count: n(['HL', 'LH']) },
            { label: 'No pattern', color: colors.value.land, count: n(['ns']) },
        ];
    }
    const labels = legendLabels(breaks.value, FORMAT[metric.value]);
    const counts = [0, 0, 0, 0, 0];
    for (const r of props.rows) {
        const b = binOf(value(r), breaks.value);
        if (b !== null) counts[b]++;
    }
    return labels.map((label, i) => ({ label, color: colors.value[`q${i}` as 'q0'], count: counts[i] }));
});
const noValue = computed(() => Object.values(fills.value).filter((f) => f === null).length);
const note = computed(() =>
    isCluster.value ? "Local Moran's I on applications per 1,000, four nearest neighbours. Only clusters with p below 0.05 are coloured." : metric.value === 'score' ? 'Uses the weights set on the Priority list.' : undefined,
);
const metricLabel = computed(() => props.metrics.find((m) => m.key === metric.value)?.label ?? '');

const row = computed(() => props.rows.find((r) => r.psgc === selected.value) ?? first);
const options = computed(() => [...props.rows].sort((a, b) => a.name.localeCompare(b.name) || a.province.localeCompare(b.province)));
</script>

<template>
    <Head title="Map" />
        <div class="lg:relative lg:h-[calc(100svh-8.75rem)] lg:min-h-[38rem]">
            <div class="h-[56svh] min-h-[22rem] lg:absolute lg:inset-0 lg:h-auto">
                <MunicipalityMap :shape="shape" :fills="fills" :selected="selected" :tips="tips" controls-class="lg:right-[24rem]" :padding="padding" @select="selected = $event" />
            </div>

            <div class="grid gap-4 p-4 lg:contents">
              <div class="grid gap-4 lg:pointer-events-none lg:absolute lg:bottom-4 lg:left-4 lg:top-4 lg:w-[21rem] lg:grid-rows-[minmax(0,1fr)_auto] [&>*]:pointer-events-auto">
                <section class="float grid content-start gap-4 p-4 lg:overflow-y-auto" aria-labelledby="map-title">
                    <div>
                        <h1 id="map-title" class="text-xl font-bold leading-tight">Participation across Caraga</h1>
                        <p class="text-sm text-muted">{{ dataset.cycle }} cycle</p>
                    </div>
                    <DataNotices :rows="rows" />
                    <div role="radiogroup" aria-label="Colour the map by" class="grid gap-0.5">
                        <button
                            v-for="m in metrics"
                            :key="m.key"
                            type="button"
                            role="radio"
                            :aria-checked="metric === m.key"
                            class="rounded px-3 py-1.5 text-left transition-colors hover:bg-surface-2 aria-checked:bg-accent-soft"
                            @click="metric = m.key"
                        >
                            <span class="block text-sm font-semibold" :class="metric === m.key ? 'text-accent' : ''">{{ m.label }}</span>
                            <span v-if="metric === m.key" class="block text-[0.8125rem] text-muted">{{ METRIC_HELP[m.key] }}</span>
                        </button>
                    </div>
                    <div class="grid gap-1"><Segmented v-model="shape" label="Map shape" :options="[{ key: 'tile', label: 'Tiles' }, { key: 'boundary', label: 'Boundaries' }]" /><p class="text-[0.75rem] text-muted">{{ shape === 'tile' ? 'One hexagon per municipality, placed near its real location.' : 'Official boundaries, simplified.' }}</p></div>
                </section>

                <section class="float p-4" aria-label="Legend">
                    <MapLegend :title="metricLabel" :items="legend" :no-value="noValue" :note="note" />
                </section>
              </div>

                <aside class="float grid gap-4 p-4 lg:absolute lg:right-4 lg:top-4 lg:max-h-[calc(100%-2rem)] lg:w-[22rem] lg:overflow-y-auto" aria-label="Selected municipality">
                    <SelectField id="map-municipality" v-model="selected" label="Municipality">
                        <option v-for="r in options" :key="r.psgc" :value="r.psgc">{{ r.name }} ({{ r.province }})</option>
                    </SelectField>
                    <div aria-live="polite">
                    <MunicipalityDetail :row="row" :all="rows" :rank="rank.get(row.psgc)?.rank ?? null" :ranked="rankedCount" :cycle="dataset.cycle" :prior-cycle="dataset.priorCycle" />
                    </div>
                </aside>
            </div>
        </div>
</template>
