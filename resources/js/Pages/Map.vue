<script setup lang="ts">
import MunicipalityDetail from '@/Components/MunicipalityDetail.vue';
import MunicipalityMap from '@/Components/MunicipalityMap.vue';
import Segmented from '@/Components/Segmented.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { binOf, legendLabels, quantileBreaks } from '@/lib/breaks';
import { fixed, pct, signedPct } from '@/lib/format';
import { useThemeColors } from '@/lib/themeColors';
import { useWeights } from '@/lib/useWeights';
import { PageProps } from '@/types';
import { MunicipalityRow } from '@/types/rows';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{ rows: MunicipalityRow[]; metrics: { key: string; label: string }[] }>();
const dataset = computed(() => usePage<PageProps>().props.dataset!);
const colors = useThemeColors();
const { scored } = useWeights(computed(() => props.rows));

const rank = computed(() => new Map(scored.value.map((s) => [s.psgc, s])));
const rankedCount = computed(() => scored.value.filter((s) => s.rank !== null).length);

const metric = ref<string>('rate');
const wanted = new URLSearchParams(window.location.search).get('psgc');
const first = props.rows.find((r) => r.psgc === wanted) ?? props.rows.find((r) => rank.value.get(r.psgc)?.rank === 1) ?? props.rows[0];
const selected = ref<string>(first.psgc);

const FORMAT: Record<string, (v: number) => string> = {
    rate: (v) => fixed(v, 1),
    attendance: (v) => pct(v),
    change: (v) => signedPct(v),
    score: (v) => fixed(v, 0),
};

const value = (r: MunicipalityRow): number | null =>
    ({
        rate: r.rate_per_1000,
        attendance: r.attendance,
        change: r.change,
        score: rank.value.get(r.psgc)?.score ?? null,
        cluster: null,
    })[metric.value] ?? null;

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

const labels = computed(() =>
    Object.fromEntries(
        props.rows.map((r) => {
            const v = value(r);
            const shown = isCluster.value ? (r.lisa_cluster ?? 'no data') : v === null ? 'not shown' : FORMAT[metric.value](v);
            return [r.psgc, `${r.name}: ${shown}`];
        }),
    ),
);

const legend = computed(() =>
    isCluster.value
        ? [
              { label: 'Hot spot', color: colors.value.q4 },
              { label: 'Cold spot', color: colors.value.crit },
              { label: 'Outlier', color: colors.value.warn },
              { label: 'No clear pattern', color: colors.value.land },
          ]
        : legendLabels(breaks.value, FORMAT[metric.value]).map((label, i) => ({ label, color: colors.value[`q${i}` as 'q0'] })),
);

const row = computed(() => props.rows.find((r) => r.psgc === selected.value) ?? first);
</script>

<template>
    <Head title="Map" />
    <AppLayout>
        <div class="grid gap-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <p class="label">Step 2 · Where</p>
                    <h1 class="mt-1 text-3xl font-semibold">Participation across Caraga, {{ dataset.cycle }} cycle</h1>
                    <p class="mt-2 text-muted">Select a municipality for its counts, rates and data status.</p>
                </div>
                <Segmented v-model="metric" label="Map metric" :options="metrics.map((m) => ({ key: m.key, label: m.label }))" />
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <div class="grid gap-3">
                    <MunicipalityMap :fills="fills" :selected="selected" :labels="labels" @select="selected = $event" />
                    <ul class="flex flex-wrap items-center gap-x-4 gap-y-1 text-xs text-muted" aria-label="Legend">
                        <li v-for="l in legend" :key="l.label" class="flex items-center gap-1.5">
                            <i class="inline-block h-2.5 w-5 border border-line" :style="{ background: l.color }" />{{ l.label }}
                        </li>
                        <li class="flex items-center gap-1.5"><i class="inline-block h-2.5 w-5 border border-line bg-surface" />Not shown (suppressed or missing)</li>
                    </ul>
                    <p v-if="isCluster" class="text-xs text-muted">Local Moran's I on applications per 1,000 with the four nearest neighbours. Only clusters with p below 0.05 are coloured.</p>
                    <p v-if="metric === 'score'" class="text-xs text-muted">Priority score uses the weights set on the Priority list.</p>
                </div>
                <aside class="panel p-4" aria-live="polite">
                    <MunicipalityDetail
                        :row="row"
                        :all="rows"
                        :rank="rank.get(row.psgc)?.rank ?? null"
                        :ranked="rankedCount"
                        :cycle="dataset.cycle"
                        :prior-cycle="dataset.priorCycle"
                    />
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
