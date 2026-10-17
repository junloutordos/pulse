<script setup lang="ts">
import DataNotices from '@/Components/DataNotices.vue';
import MunicipalityDetail from '@/Components/MunicipalityDetail.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fixed, pct } from '@/lib/format';
import { useWeights } from '@/lib/useWeights';
import { PageProps } from '@/types';
import { MunicipalityRow } from '@/types/rows';
import { Head, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps<{
    rows: MunicipalityRow[];
    regionMedians: { rate: number | null; attendance: number | null };
    hidden: { suppressed: number; missing: number };
}>();
const dataset = computed(() => usePage<PageProps>().props.dataset!);
const { scored } = useWeights(computed(() => props.rows));
const ranks = computed(() => new Map(scored.value.map((s) => [s.psgc, s.rank])));
const rankedCount = computed(() => scored.value.filter((s) => s.rank !== null).length);

const wanted = new URLSearchParams(window.location.search).get('psgc');
const first = props.rows.find((r) => r.psgc === wanted) ?? props.rows.find((r) => r.status === 'ok') ?? props.rows[0];
const selected = ref(first.psgc);
const row = computed(() => props.rows.find((r) => r.psgc === selected.value) ?? first);

const points = computed(() => props.rows.filter((r) => (r.status === 'ok' || r.status === 'low') && r.rate_per_1000 !== null && r.attendance !== null));

// Chart geometry: one scale per axis, ticks placed with the same scale.
const W = 600, H = 400, L = 52, R = 16, T = 16, B = 48;
const xMax = computed(() => (points.value.length ? Math.max(10, Math.ceil(Math.max(...points.value.map((p) => p.rate_per_1000!)) / 10) * 10) : 10));
const yMin = computed(() => (points.value.length ? Math.max(0, Math.floor(Math.min(...points.value.map((p) => p.attendance!)) * 10) / 10) : 0));
const x = (v: number) => L + (v / xMax.value) * (W - L - R);
const y = (v: number) => T + (1 - (v - yMin.value) / (1 - yMin.value)) * (H - T - B);
const xTicks = computed(() => Array.from({ length: xMax.value / 10 + 1 }, (_, i) => i * 10));
const yTicks = computed(() => {
    const out: number[] = [];
    for (let t = Math.round(yMin.value * 10); t <= 10; t++) out.push(t / 10);
    return out;
});
const mx = computed(() => props.regionMedians.rate);
const my = computed(() => props.regionMedians.attendance);
</script>

<template>
    <Head title="Pipeline" />
    <AppLayout>
        <div class="grid gap-6">
            <div class="max-w-3xl">
                <p class="label">Step 3 · Which stage</p>
                <h1 class="mt-1 text-3xl font-semibold">Is the gap in applying or in sitting the exam?</h1>
                <p class="mt-2 text-muted">
                    The same low numbers can need different action. Few applications call for information campaigns. Low attendance calls for reminders and an access review.
                </p>
            </div>
            <DataNotices :rows="rows" />

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="panel p-4" aria-labelledby="scatter">
                    <h2 id="scatter" class="sr-only">Applications per 1,000 against exam attendance</h2>
                    <p v-if="!points.length" class="p-6 text-sm text-muted">Nothing to chart: no municipality has both applications per 1,000 and exam attendance.</p>
                    <svg v-else :viewBox="`0 0 ${W} ${H}`" class="h-auto w-full" role="img" aria-label="Scatter of applications per 1,000 Grade 6 learners against exam attendance">
                        <rect v-if="mx !== null && my !== null" :x="L" :y="y(my)" :width="x(mx) - L" :height="H - B - y(my)" class="fill-crit-bg" />
                        <g class="stroke-line">
                            <line v-for="t in xTicks" :key="`gx${t}`" :x1="x(t)" :x2="x(t)" :y1="T" :y2="H - B" />
                            <line v-for="t in yTicks" :key="`gy${t}`" :x1="L" :x2="W - R" :y1="y(t)" :y2="y(t)" />
                        </g>
                        <g class="fill-muted text-[11px]" style="font-family: var(--font-data)">
                            <text v-for="t in xTicks" :key="`tx${t}`" :x="x(t)" :y="H - B + 16" text-anchor="middle">{{ t }}</text>
                            <text v-for="t in yTicks" :key="`ty${t}`" :x="L - 8" :y="y(t) + 4" text-anchor="end">{{ Math.round(t * 100) }}%</text>
                        </g>
                        <g v-if="mx !== null && my !== null" class="stroke-muted" stroke-dasharray="4 3">
                            <line :x1="x(mx)" :x2="x(mx)" :y1="T" :y2="H - B" />
                            <line :x1="L" :x2="W - R" :y1="y(my)" :y2="y(my)" />
                        </g>
                        <g class="fill-muted text-[11px]">
                            <text :x="L + 8" :y="T + 14">Few apply, most sit the exam</text>
                            <text :x="W - R - 8" :y="T + 14" text-anchor="end">Strong on both</text>
                            <text :x="L + 8" :y="H - B - 8" class="fill-crit font-semibold">Low on both</text>
                            <text :x="W - R - 8" :y="H - B - 8" text-anchor="end">Many apply, many miss the exam</text>
                            <text :x="(L + W - R) / 2" :y="H - 8" text-anchor="middle">Applications per 1,000 Grade 6 learners</text>
                        </g>
                        <circle
                            v-for="p in points"
                            :key="p.psgc"
                            :cx="x(p.rate_per_1000!)"
                            :cy="y(p.attendance!)"
                            :r="p.psgc === selected ? 8 : 5"
                            :class="[p.status === 'low' ? 'fill-surface' : 'fill-accent', p.psgc === selected ? 'stroke-ink' : 'stroke-accent']"
                            :stroke-width="p.psgc === selected ? 2.5 : 1.5"
                            class="cursor-pointer"
                            tabindex="0"
                            role="button"
                            :aria-label="`${p.name}: ${fixed(p.rate_per_1000)} per 1,000, ${pct(p.attendance)} attended`"
                            @click="selected = p.psgc"
                            @keydown.enter="selected = p.psgc"
                        >
                            <title>{{ p.name }}: {{ fixed(p.rate_per_1000) }} per 1,000, {{ pct(p.attendance) }} attended</title>
                        </circle>
                    </svg>
                    <p class="mt-2 text-xs text-muted">
                        Dashed lines are regional medians. Hollow dots have fewer than 15 applicants.
                        {{ hidden.suppressed }} suppressed and {{ hidden.missing }} missing municipalities are not drawn.
                    </p>
                </section>
                <aside class="panel p-4" aria-live="polite">
                    <MunicipalityDetail :row="row" :all="rows" :rank="ranks.get(row.psgc) ?? null" :ranked="rankedCount" :cycle="dataset.cycle" :prior-cycle="dataset.priorCycle" />
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
