<script setup lang="ts">
import DataNotices from '@/Components/DataNotices.vue';
import MunicipalityDetail from '@/Components/MunicipalityDetail.vue';
import PageHeader from '@/Components/PageHeader.vue';
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
const W = 640, H = 420, L = 56, R = 20, T = 20, B = 52;
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

// Name the selected municipality and the few furthest into the low-low corner, skipping labels that would collide.
const labelled = computed(() => {
    const corner = [...points.value]
        .map((p) => ({ p, d: (p.rate_per_1000! / xMax.value) + (p.attendance! - yMin.value) / (1 - yMin.value) }))
        .sort((a, b) => a.d - b.d)
        .slice(0, 4)
        .map((c) => c.p);
    const picks = [points.value.find((p) => p.psgc === selected.value), ...corner].filter((p): p is MunicipalityRow => !!p);
    const placed: { px: number; py: number }[] = [];
    return picks.filter((p, i, all) => {
        if (all.findIndex((q) => q.psgc === p.psgc) !== i) return false;
        const px = x(p.rate_per_1000!), py = y(p.attendance!);
        if (placed.some((q) => Math.abs(q.px - px) < 70 && Math.abs(q.py - py) < 16)) return false;
        placed.push({ px, py });
        return true;
    });
});
</script>

<template>
    <Head title="Pipeline" />
    <AppLayout>
        <div class="grid gap-8">
            <PageHeader>
                <template #title>Is the gap in applying or in sitting the exam?</template>
                The same low numbers can need different action. Few applications call for information campaigns. Low attendance calls for reminders and an access review.
            </PageHeader>
            <DataNotices :rows="rows" />

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
                <section class="panel p-5" aria-labelledby="scatter">
                    <h2 id="scatter" class="text-lg font-bold">Applications against exam attendance</h2>
                    <p v-if="!points.length" class="py-16 text-center text-sm text-muted">Nothing to chart: no municipality has both applications per 1,000 and exam attendance.</p>
                    <svg v-else :viewBox="`0 0 ${W} ${H}`" class="mt-2 h-auto w-full" role="img" aria-label="Scatter of applications per 1,000 Grade 6 learners against exam attendance">
                        <rect v-if="mx !== null && my !== null" :x="L" :y="y(my)" :width="x(mx) - L" :height="H - B - y(my)" class="fill-crit-bg" opacity="0.7" />
                        <g class="stroke-line" stroke-width="1">
                            <line v-for="t in xTicks" :key="`gx${t}`" :x1="x(t)" :x2="x(t)" :y1="T" :y2="H - B" opacity="0.6" />
                            <line v-for="t in yTicks" :key="`gy${t}`" :x1="L" :x2="W - R" :y1="y(t)" :y2="y(t)" opacity="0.6" />
                        </g>
                        <g class="fill-muted text-xs">
                            <text v-for="t in xTicks" :key="`tx${t}`" :x="x(t)" :y="H - B + 18" text-anchor="middle" class="num">{{ t }}</text>
                            <text v-for="t in yTicks" :key="`ty${t}`" :x="L - 10" :y="y(t) + 4" text-anchor="end" class="num">{{ Math.round(t * 100) }}%</text>
                        </g>
                        <g v-if="mx !== null && my !== null" class="stroke-muted" stroke-dasharray="4 4" opacity="0.7">
                            <line :x1="x(mx)" :x2="x(mx)" :y1="T" :y2="H - B" />
                            <line :x1="L" :x2="W - R" :y1="y(my)" :y2="y(my)" />
                        </g>
                        <g class="fill-muted text-xs">
                            <text :x="L + 10" :y="T + 16">Few apply, most sit the exam</text>
                            <text :x="W - R - 10" :y="T + 16" text-anchor="end">Strong on both</text>
                            <text :x="L + 10" :y="H - B - 10" class="fill-crit font-semibold">Low on both</text>
                            <text :x="W - R - 10" :y="H - B - 10" text-anchor="end">Many apply, many miss the exam</text>
                            <text :x="(L + W - R) / 2" :y="H - 8" text-anchor="middle" class="text-[0.8125rem]">Applications per 1,000 Grade 6 learners</text>
                        </g>
                        <circle
                            v-for="p in points"
                            :key="p.psgc"
                            :cx="x(p.rate_per_1000!)"
                            :cy="y(p.attendance!)"
                            :r="p.psgc === selected ? 8 : 5.5"
                            :class="[p.status === 'low' ? 'fill-surface stroke-accent' : 'fill-accent stroke-surface', p.psgc === selected ? '!stroke-ink' : '']"
                            :stroke-width="p.psgc === selected ? 3 : p.status === 'low' ? 2 : 1.5"
                            class="cursor-pointer outline-none transition-[r] duration-150 focus-visible:stroke-ink"
                            tabindex="0"
                            role="button"
                            :aria-label="`${p.name}: ${fixed(p.rate_per_1000)} per 1,000, ${pct(p.attendance)} attended`"
                            @click="selected = p.psgc"
                            @keydown.enter="selected = p.psgc"
                        >
                            <title>{{ p.name }}: {{ fixed(p.rate_per_1000) }} per 1,000, {{ pct(p.attendance) }} attended</title>
                        </circle>
                        <g class="pointer-events-none text-xs font-semibold" style="paint-order: stroke; stroke-width: 4px" stroke-linejoin="round">
                            <text v-for="p in labelled" :key="`l${p.psgc}`" :x="x(p.rate_per_1000!) + 11" :y="y(p.attendance!) - 9" class="fill-ink stroke-surface">{{ p.name }}</text>
                        </g>
                    </svg>
                    <p class="mt-3 text-[0.8125rem] text-muted">
                        Dashed lines are regional medians. Hollow dots have fewer than 15 applicants. {{ hidden.suppressed }} suppressed and {{ hidden.missing }} missing municipalities are not drawn.
                    </p>
                </section>
                <aside class="panel p-5" aria-live="polite">
                    <MunicipalityDetail :row="row" :all="rows" :rank="ranks.get(row.psgc) ?? null" :ranked="rankedCount" :cycle="dataset.cycle" :prior-cycle="dataset.priorCycle" />
                </aside>
            </div>
        </div>
    </AppLayout>
</template>
