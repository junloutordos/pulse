<script setup lang="ts">
import DataNotices from '@/Components/DataNotices.vue';
import MunicipalityDetail from '@/Components/MunicipalityDetail.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Segmented from '@/Components/Segmented.vue';
import { fixed, pct } from '@/lib/format';
import { nearestPoint, pointsInRect, Quadrant, QUADRANT_LABEL, quadrantOf } from '@/lib/scatter';
import { initialSelection, select } from '@/lib/selection';
import { useWeights } from '@/lib/useWeights';
import { PageProps } from '@/types';
import { MunicipalityRow } from '@/types/rows';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

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
const firstOk = props.rows.find((r) => r.status === 'ok')?.psgc ?? props.rows[0].psgc;
const selected = ref(initialSelection(wanted, props.rows.map((r) => r.psgc), firstOk));
const first = props.rows.find((r) => r.psgc === selected.value) ?? props.rows[0];
watch(selected, (v) => select(v), { immediate: true });
const row = computed(() => props.rows.find((r) => r.psgc === selected.value) ?? first);
const byPsgc = computed(() => new Map(props.rows.map((r) => [r.psgc, r])));

const points = computed(() => props.rows.filter((r) => (r.status === 'ok' || r.status === 'low') && r.rate_per_1000 !== null && r.attendance !== null));

// Chart geometry: one scale per axis, ticks placed with the same scale.
const W = 640, H = 420, L = 56, R = 20, T = 20, B = 52;
const xMax = computed(() => (points.value.length ? Math.max(10, Math.ceil(Math.max(...points.value.map((p) => p.rate_per_1000!)) / 10) * 10) : 10));
const yMin = computed(() => (points.value.length ? Math.max(0, Math.floor(Math.min(...points.value.map((p) => p.attendance!)) * 10) / 10) : 0));
const x = (v: number) => L + (v / xMax.value) * (W - L - R);
const y = (v: number) => T + (1 - (v - yMin.value) / (1 - yMin.value)) * (H - T - B);
const unx = (sx: number) => ((sx - L) / (W - L - R)) * xMax.value;
const uny = (sy: number) => yMin.value + (1 - (sy - T) / (H - T - B)) * (1 - yMin.value);
const xTicks = computed(() => Array.from({ length: xMax.value / 10 + 1 }, (_, i) => i * 10));
const yTicks = computed(() => {
    const out: number[] = [];
    for (let t = Math.round(yMin.value * 10); t <= 10; t++) out.push(t / 10);
    return out;
});
const mx = computed(() => props.regionMedians.rate);
const my = computed(() => props.regionMedians.attendance);
const medians = computed(() => (mx.value !== null && my.value !== null ? { x: mx.value, y: my.value } : null));

// ---- Reading the chart: hover card, a group chosen by quadrant or by dragging, and a table alternative.
const svg = ref<SVGSVGElement>();
const screenPoints = computed(() => points.value.map((p) => ({ psgc: p.psgc, x: x(p.rate_per_1000!), y: y(p.attendance!) })));
const dataPoints = computed(() => points.value.map((p) => ({ psgc: p.psgc, x: p.rate_per_1000!, y: p.attendance! })));
const hover = ref<{ psgc: string; left: number; top: number } | null>(null);
const brush = ref<{ x0: number; y0: number; x1: number; y1: number } | null>(null);
const group = ref<{ label: string; ids: Set<string> } | null>(null);
const view = ref<'chart' | 'table'>('chart');

function toSvg(e: PointerEvent): { sx: number; sy: number; box: DOMRect } {
    const box = svg.value!.getBoundingClientRect();
    return { sx: ((e.clientX - box.left) * W) / box.width, sy: ((e.clientY - box.top) * H) / box.height, box };
}
function onMove(e: PointerEvent) {
    const { sx, sy, box } = toSvg(e);
    if (brush.value) {
        brush.value = { ...brush.value, x1: sx, y1: sy };
        return;
    }
    const near = nearestPoint(screenPoints.value, sx, sy, 16);
    hover.value = near ? { psgc: near.psgc, left: (near.x / W) * box.width, top: (near.y / H) * box.height } : null;
}
function onDown(e: PointerEvent) {
    const { sx, sy } = toSvg(e);
    svg.value!.setPointerCapture(e.pointerId);
    brush.value = { x0: sx, y0: sy, x1: sx, y1: sy };
    hover.value = null;
}
function onUp() {
    const b = brush.value;
    brush.value = null;
    if (!b) return;
    if (Math.hypot(b.x1 - b.x0, b.y1 - b.y0) < 6) {
        const near = nearestPoint(screenPoints.value, b.x0, b.y0, 16);
        if (near) selected.value = near.psgc;
        else group.value = null;
        return;
    }
    const inside = pointsInRect(dataPoints.value, { x0: unx(b.x0), y0: uny(b.y0), x1: unx(b.x1), y1: uny(b.y1) });
    group.value = inside.length ? { label: 'Selected area', ids: new Set(inside.map((p) => p.psgc)) } : null;
}
function pickQuadrant(q: Quadrant) {
    if (!medians.value) return;
    const ids = dataPoints.value.filter((p) => quadrantOf(p, medians.value!) === q).map((p) => p.psgc);
    group.value = group.value?.label === QUADRANT_LABEL[q] ? null : { label: QUADRANT_LABEL[q], ids: new Set(ids) };
}
const dimmed = (psgc: string) => group.value !== null && !group.value.ids.has(psgc);
const groupRows = computed(() => (group.value ? [...group.value.ids].map((id) => byPsgc.value.get(id)!).sort((a, b) => a.attendance! - b.attendance!) : []));
const tableRows = computed(() => [...points.value].sort((a, b) => a.attendance! - b.attendance!));
const brushBox = computed(() => (brush.value ? { x: Math.min(brush.value.x0, brush.value.x1), y: Math.min(brush.value.y0, brush.value.y1), w: Math.abs(brush.value.x1 - brush.value.x0), h: Math.abs(brush.value.y1 - brush.value.y0) } : null));

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
const QUADRANTS = Object.keys(QUADRANT_LABEL) as Quadrant[];
</script>

<template>
    <Head title="Pipeline" />
    <div class="grid gap-8">
        <PageHeader>
            <template #title>Is the gap in applying or in sitting the exam?</template>
            The same low numbers can need different action. Few applications call for information campaigns. Low attendance calls for reminders and an access review.
        </PageHeader>
        <DataNotices :rows="rows" />

        <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <section class="panel p-5" aria-labelledby="scatter">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <h2 id="scatter" class="text-lg font-bold">Applications against exam attendance</h2>
                    <Segmented v-model="view" label="Show as" :options="[{ key: 'chart', label: 'Chart' }, { key: 'table', label: 'Table' }]" />
                </div>

                <p v-if="!points.length" class="py-16 text-center text-sm text-muted">Nothing to chart: no municipality has both applications per 1,000 and exam attendance.</p>

                <template v-else-if="view === 'chart'">
                    <div class="mt-3 flex flex-wrap gap-1.5" role="group" aria-label="Pick a group of municipalities">
                        <button v-for="q in QUADRANTS" :key="q" type="button" class="pill" :class="group?.label === QUADRANT_LABEL[q] ? 'bg-ink text-bg' : 'pill-plain hover:bg-line'" :aria-pressed="group?.label === QUADRANT_LABEL[q]" :disabled="!medians" @click="pickQuadrant(q)">
                            {{ QUADRANT_LABEL[q] }}
                        </button>
                    </div>
                    <div class="relative mt-2">
                        <svg
                            ref="svg"
                            :viewBox="`0 0 ${W} ${H}`"
                            class="h-auto w-full touch-none select-none"
                            role="group"
                            aria-label="Scatter of applications per 1,000 Grade 6 learners against exam attendance. Use the group buttons above, or the table view, for a keyboard alternative."
                            @pointermove="onMove"
                            @pointerdown="onDown"
                            @pointerup="onUp"
                            @pointerleave="hover = null"
                        >
                            <rect v-if="medians" :x="L" :y="y(my!)" :width="x(mx!) - L" :height="H - B - y(my!)" class="fill-crit-bg" opacity="0.7" />
                            <g class="stroke-line" stroke-width="1">
                                <line v-for="t in xTicks" :key="`gx${t}`" :x1="x(t)" :x2="x(t)" :y1="T" :y2="H - B" opacity="0.6" />
                                <line v-for="t in yTicks" :key="`gy${t}`" :x1="L" :x2="W - R" :y1="y(t)" :y2="y(t)" opacity="0.6" />
                            </g>
                            <g class="fill-muted text-xs">
                                <text v-for="t in xTicks" :key="`tx${t}`" :x="x(t)" :y="H - B + 18" text-anchor="middle" class="num">{{ t }}</text>
                                <text v-for="t in yTicks" :key="`ty${t}`" :x="L - 10" :y="y(t) + 4" text-anchor="end" class="num">{{ Math.round(t * 100) }}%</text>
                            </g>
                            <g v-if="medians" class="stroke-muted" stroke-dasharray="4 4" opacity="0.7">
                                <line :x1="x(mx!)" :x2="x(mx!)" :y1="T" :y2="H - B" />
                                <line :x1="L" :x2="W - R" :y1="y(my!)" :y2="y(my!)" />
                            </g>
                            <g class="fill-muted text-xs">
                                <text :x="L + 10" :y="T + 16">Few apply, most sit the exam</text>
                                <text :x="W - R - 10" :y="T + 16" text-anchor="end">Strong on both</text>
                                <text :x="L + 10" :y="H - B - 10" class="fill-crit font-semibold">Low on both</text>
                                <text :x="W - R - 10" :y="H - B - 10" text-anchor="end">Many apply, many miss the exam</text>
                                <text :x="(L + W - R) / 2" :y="H - 8" text-anchor="middle" class="text-[0.8125rem]">Applications per 1,000 Grade 6 learners</text>
                            </g>
                            <g v-if="hover">
                                <line :x1="x(byPsgc.get(hover.psgc)!.rate_per_1000!)" :x2="x(byPsgc.get(hover.psgc)!.rate_per_1000!)" :y1="T" :y2="H - B" class="stroke-ink" stroke-width="1" opacity="0.25" />
                                <line :x1="L" :x2="W - R" :y1="y(byPsgc.get(hover.psgc)!.attendance!)" :y2="y(byPsgc.get(hover.psgc)!.attendance!)" class="stroke-ink" stroke-width="1" opacity="0.25" />
                            </g>
                            <rect v-if="brushBox" :x="brushBox.x" :y="brushBox.y" :width="brushBox.w" :height="brushBox.h" class="fill-accent stroke-accent" fill-opacity="0.12" stroke-width="1.5" />
                            <circle
                                v-for="p in points"
                                :key="p.psgc"
                                :cx="x(p.rate_per_1000!)"
                                :cy="y(p.attendance!)"
                                :r="p.psgc === selected ? 8 : hover?.psgc === p.psgc ? 7 : 5.5"
                                :class="[p.status === 'low' ? 'fill-surface stroke-accent' : 'fill-accent stroke-surface', p.psgc === selected ? '!stroke-ink' : '']"
                                :stroke-width="p.psgc === selected ? 3 : p.status === 'low' ? 2 : 1.5"
                                :opacity="dimmed(p.psgc) ? 0.25 : 1"
                                class="cursor-pointer outline-none transition-[r,opacity] duration-150 focus-visible:stroke-ink"
                                tabindex="0"
                                role="button"
                                :aria-label="`${p.name}: ${fixed(p.rate_per_1000)} per 1,000, ${pct(p.attendance)} attended`"
                                @keydown.enter="selected = p.psgc"
                            />
                            <g class="pointer-events-none text-xs font-semibold" style="paint-order: stroke; stroke-width: 4px" stroke-linejoin="round">
                                <text v-for="p in labelled" :key="`l${p.psgc}`" :x="x(p.rate_per_1000!) + 11" :y="y(p.attendance!) - 9" class="fill-ink stroke-surface">{{ p.name }}</text>
                            </g>
                        </svg>
                        <div v-if="hover" class="float pointer-events-none absolute z-10 max-w-[14rem] px-3 py-2 text-sm" :style="{ left: `${hover.left + 14}px`, top: `${hover.top - 12}px` }">
                            <p class="font-semibold">{{ byPsgc.get(hover.psgc)!.name }}</p>
                            <p class="text-[0.8125rem] text-muted">{{ byPsgc.get(hover.psgc)!.province }}</p>
                            <p class="num mt-1 text-[0.8125rem]">{{ fixed(byPsgc.get(hover.psgc)!.rate_per_1000) }} per 1,000 · {{ pct(byPsgc.get(hover.psgc)!.attendance) }} attended</p>
                        </div>
                    </div>
                    <p class="mt-2 text-[0.8125rem] text-muted">
                        Drag on the chart to pick an area, or use the group buttons. Dashed lines are regional medians. Hollow dots have fewer than 15 applicants.
                        {{ hidden.suppressed }} suppressed and {{ hidden.missing }} missing municipalities are not drawn.
                    </p>
                </template>

                <div v-else class="panel mt-3 max-h-[28rem] overflow-auto">
                    <table class="data-table">
                        <caption class="sr-only">All municipalities with both values, lowest attendance first</caption>
                        <thead><tr><th>Municipality</th><th>Province</th><th class="text-right">Apps per 1,000</th><th class="text-right">Attendance</th><th>Sample</th></tr></thead>
                        <tbody>
                            <tr v-for="p in tableRows" :key="p.psgc" :class="{ 'is-selected': p.psgc === selected }" @click="selected = p.psgc">
                                <td class="font-semibold">{{ p.name }}</td><td class="text-muted">{{ p.province }}</td>
                                <td class="num text-right">{{ fixed(p.rate_per_1000) }}</td><td class="num text-right">{{ pct(p.attendance) }}</td>
                                <td><span class="pill" :class="p.status === 'low' ? 'pill-warn' : 'pill-ok'">{{ p.status === 'low' ? 'Low sample' : 'Complete' }}</span></td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-if="group" class="mt-4 rounded-md bg-surface-2 p-4" aria-live="polite">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <p class="font-semibold">{{ group.label }}: {{ group.ids.size }} municipalities</p>
                        <button type="button" class="btn btn-sm btn-quiet" @click="group = null">Clear</button>
                    </div>
                    <ul class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-sm">
                        <li v-for="m in groupRows.slice(0, 14)" :key="m.psgc">
                            <button type="button" class="hover:underline" @click="selected = m.psgc">{{ m.name }}</button>
                            <span class="num text-muted"> {{ pct(m.attendance) }}</span>
                        </li>
                        <li v-if="groupRows.length > 14" class="text-muted">and {{ groupRows.length - 14 }} more</li>
                    </ul>
                    <Link :href="route('priority')" class="mt-3 inline-block text-sm font-semibold text-accent hover:underline">See how they rank on the Priority list</Link>
                </div>
            </section>
            <aside class="panel p-5" aria-live="polite">
                <MunicipalityDetail :row="row" :all="rows" :rank="ranks.get(row.psgc) ?? null" :ranked="rankedCount" :cycle="dataset.cycle" :prior-cycle="dataset.priorCycle" />
            </aside>
        </div>
    </div>
</template>
