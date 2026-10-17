<script setup lang="ts">
import DataNotices from '@/Components/DataNotices.vue';
import EmptyState from '@/Components/EmptyState.vue';
import PageHeader from '@/Components/PageHeader.vue';
import Spark from '@/Components/Spark.vue';
import Segmented from '@/Components/Segmented.vue';
import { suggestedFocus } from '@/lib/focus';
import { fixed, pct, signedPct } from '@/lib/format';
import { PRESETS, rankBand } from '@/lib/rankBand';
import { select, selected } from '@/lib/selection';
import { notify } from '@/lib/toasts';
import { copyLink, formatWeights, parseWeights, readParam, replaceParams } from '@/lib/urlState';
import { setWeights, useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { biggestMover, rankDeltas, Ranks } from '@/lib/rankDeltas';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowDown, ArrowRight, ArrowUp, Link2 } from 'lucide-vue-next';
import { computed, onBeforeUnmount, ref, watch } from 'vue';

const props = defineProps<{ rows: MunicipalityRow[]; canPlan: boolean }>();
// A shared link can carry the weights; useWeights then re-checks them against this dataset.
const fromLink = parseWeights(readParam('w'));
if (fromLink) setWeights(fromLink);
const { weights, message, scored, available, update, applyPreset } = useWeights(computed(() => props.rows));
watch(weights, () => replaceParams({ w: formatWeights(weights) }), { deep: true, immediate: true });
async function share() {
    notify((await copyLink()) ? { message: 'Link copied' } : { message: 'Could not copy. Copy the address from the browser bar.', tone: 'error' });
}

const byPsgc = computed(() => new Map(props.rows.map((r) => [r.psgc, r])));
const ranked = computed(() => scored.value.filter((s) => s.rank !== null).sort((a, b) => a.rank! - b.rank!));
const notRanked = computed(() => scored.value.filter((s) => s.rank === null));
const weightSum = computed(() => {
    const s = scored.value.find((r) => r.score !== null);
    return (['reach', 'attendance', 'decline'] as const).reduce((sum, k) => sum + (s && s[`c_${k}`] !== null ? weights[k] : 0), 0) || 1;
});

// When weights change, rows glide to their new places and say how far each moved for a moment.
const rankMap = computed<Ranks>(() => new Map(ranked.value.map((s) => [s.psgc, s.rank!])));
const deltas = ref<Record<string, number>>({});
const announcement = ref('');
let clear = 0;
watch(rankMap, (next, prev) => {
    deltas.value = rankDeltas(prev ?? null, next);
    const mover = biggestMover(prev ?? null, next);
    announcement.value = mover ? `${byPsgc.value.get(mover.psgc)!.name} moved from rank ${mover.from} to rank ${mover.to}.` : '';
    clearTimeout(clear);
    clear = window.setTimeout(() => (deltas.value = {}), 2600);
});
onBeforeUnmount(() => clearTimeout(clear));

const bands = computed(() => new Map(ranked.value.map((s) => [s.psgc, rankBand(props.rows, PRESETS, s.psgc)])));
const segment = (c: number | null, key: 'reach' | 'attendance' | 'decline') => (c === null ? 0 : (100 * weights[key] * c) / weightSum.value);

/** Apply the change, then put the thumb back where the accepted weight is (a rejected change leaves it unchanged). */
function onSlide(key: 'reach' | 'attendance' | 'decline', el: HTMLInputElement) {
    update(key, +el.value / 100);
    el.value = String(Math.round(weights[key] * 100));
}

const SLIDERS = [
    { key: 'reach', label: 'Low applications' },
    { key: 'attendance', label: 'Low attendance' },
    { key: 'decline', label: 'Decline vs prior cycle' },
] as const;
const active = (key: 'reach' | 'attendance' | 'decline') => available.value[key];

const preset = computed({
    get: () => Object.entries(PRESETS).find(([, p]) => (['reach', 'attendance', 'decline'] as const).every((k) => Math.abs(p[k] - weights[k]) < 0.005))?.[0] ?? '',
    set: (name: string) => applyPreset(name),
});
const presetOptions = Object.keys(PRESETS).map((k) => ({ key: k, label: k }));
</script>

<template>
    <Head title="Priority list" />
        <div class="grid gap-8">
            <PageHeader>
                <template #title>Explainable outreach priority</template>
                Weights are planning preferences, not predictions. Move them and watch which places keep their rank.
                <template #actions>
                    <button type="button" class="btn h-10" @click="share"><Link2 class="h-4 w-4" aria-hidden="true" />Copy link</button>
                    <Link :href="route('planner')" class="btn btn-primary h-10">Plan outreach with these weights <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>
                </template>
            </PageHeader>
            <DataNotices :rows="rows" />

            <div class="grid items-start gap-6 xl:grid-cols-[19rem_minmax(0,1fr)]">
                <aside class="panel grid gap-5 p-5 xl:sticky xl:top-36">
                    <div>
                        <h2 class="text-lg font-bold">Weights</h2>
                        <p class="text-[0.8125rem] text-muted">What matters most when ranking.</p>
                    </div>
                    <div v-for="s in SLIDERS" :key="s.key">
                        <label class="flex items-baseline justify-between text-sm font-medium" :for="`w-${s.key}`">
                            <span>{{ s.label }}</span>
                            <span class="num font-semibold">{{ Math.round(weights[s.key] * 100) }}</span>
                        </label>
                        <input
                            :id="`w-${s.key}`"
                            type="range" min="0" max="100" step="1"
                            :value="Math.round(weights[s.key] * 100)"
                            :style="{ '--pct': `${Math.round(weights[s.key] * 100)}%` }"
                            :disabled="!active(s.key)"
                            class="range mt-2.5"
                            @input="onSlide(s.key, $event.target as HTMLInputElement)"
                        />
                        <p v-if="!active(s.key)" class="mt-1 text-[0.8125rem] text-muted">Not available in this dataset.</p>
                    </div>
                    <p v-if="message" role="alert" class="rounded bg-warn-bg px-3 py-2 text-sm text-warn">{{ message }}</p>
                    <div>
                        <p class="label mb-1.5">Presets</p>
                        <Segmented v-model="preset" label="Weight presets" :options="presetOptions" fill />
                    </div>
                    <ul class="grid gap-1.5 text-[0.8125rem] text-muted" aria-label="Score components">
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 rounded-sm bg-q4" />Low applications</li>
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 rounded-sm bg-q2" />Low attendance</li>
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 rounded-sm bg-q1" />Decline</li>
                    </ul>
                    <p class="text-[0.8125rem] text-muted"><b class="text-ink">Stable</b> means the rank stays within a band of four across all three presets and within the top 10.</p>
                </aside>

                <section class="grid gap-6" aria-labelledby="ranked">
                    <div v-if="!ranked.length" class="panel"><EmptyState title="No municipality can be ranked">Every municipality is withheld or missing data for the weights you set. Check the Data trust screen for why.</EmptyState></div>
                    <div v-else class="panel max-h-[72vh] overflow-auto">
                        <table class="data-table">
                            <caption id="ranked" class="sr-only">Municipalities ranked by priority score</caption>
                            <thead>
                                <tr>
                                    <th class="text-right">Rank</th><th>Municipality</th><th>Score</th>
                                    <th class="text-right">Apps per 1,000</th><th class="text-right">Attendance</th><th class="whitespace-nowrap">Change vs prior</th>
                                    <th>Rank band</th><th>Suggested focus</th>
                                </tr>
                            </thead>
                            <TransitionGroup tag="tbody" name="rows">
                                <tr v-for="s in ranked" :key="s.psgc" :class="{ 'is-selected': s.psgc === selected }" @click="select(s.psgc)">
                                    <td class="text-right">
                                        <span class="num font-display text-xl font-bold">{{ s.rank }}</span>
                                        <span v-if="deltas[s.psgc]" class="num ml-1 inline-flex items-center text-[0.75rem] font-semibold" :class="deltas[s.psgc] > 0 ? 'text-accent' : 'text-muted'" aria-hidden="true">
                                            <ArrowUp v-if="deltas[s.psgc] > 0" class="h-3 w-3" /><ArrowDown v-else class="h-3 w-3" />{{ Math.abs(deltas[s.psgc]) }}
                                        </span>
                                    </td>
                                    <td class="min-w-[11rem]">
                                        <Link :href="route('map', { psgc: s.psgc })" class="font-semibold hover:underline">{{ byPsgc.get(s.psgc)!.name }}</Link>
                                        <div class="text-[0.8125rem] text-muted">{{ byPsgc.get(s.psgc)!.province }}</div>
                                    </td>
                                    <td>
                                        <div class="flex items-center gap-2.5">
                                            <span class="num w-7 text-right font-semibold">{{ fixed(s.score, 0) }}</span>
                                            <div class="flex h-2 w-28 overflow-hidden rounded-full bg-surface-2" role="img" :aria-label="`Score ${fixed(s.score, 0)}: applications ${fixed(segment(s.c_reach, 'reach'), 0)}, attendance ${fixed(segment(s.c_attendance, 'attendance'), 0)}, decline ${fixed(segment(s.c_decline, 'decline'), 0)}`">
                                                <span class="bg-q4 transition-[width] duration-[var(--dur-3)] ease-[var(--ease-out)]" :style="{ width: `${segment(s.c_reach, 'reach')}%` }" />
                                                <span class="bg-q2 transition-[width] duration-[var(--dur-3)] ease-[var(--ease-out)]" :style="{ width: `${segment(s.c_attendance, 'attendance')}%` }" />
                                                <span class="bg-q1 transition-[width] duration-[var(--dur-3)] ease-[var(--ease-out)]" :style="{ width: `${segment(s.c_decline, 'decline')}%` }" />
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num text-right">{{ fixed(byPsgc.get(s.psgc)!.rate_per_1000) }}</td>
                                    <td class="num text-right">{{ pct(byPsgc.get(s.psgc)!.attendance) }}</td>
                                    <td>
                                        <div class="flex items-center gap-2">
                                            <Spark :from="byPsgc.get(s.psgc)!.prior_applicants" :to="byPsgc.get(s.psgc)!.applicants" :label="`${byPsgc.get(s.psgc)!.name} applicants`" />
                                            <span class="num w-10 text-right text-[0.8125rem]">{{ signedPct(byPsgc.get(s.psgc)!.change) }}</span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap">
                                        <template v-if="bands.get(s.psgc)">
                                            <span class="pill" :class="bands.get(s.psgc)!.stable ? 'pill-ok' : 'pill-warn'">{{ bands.get(s.psgc)!.stable ? 'Stable' : 'Sensitive' }}</span>
                                            <span class="num ml-1.5 text-[0.8125rem] text-muted">#{{ bands.get(s.psgc)!.lo }} to #{{ bands.get(s.psgc)!.hi }}</span>
                                        </template>
                                    </td>
                                    <td class="min-w-[12rem] text-[0.8125rem]">{{ suggestedFocus(s, weights) }}</td>
                                </tr>
                            </TransitionGroup>
                        </table>
                    </div>
                    <p class="sr-only" role="status" aria-live="polite">{{ announcement }}</p>

                    <div>
                        <h2 class="mb-2 text-lg font-bold">Not ranked ({{ notRanked.length }})</h2>
                        <ul class="flex flex-wrap gap-2">
                            <li v-for="s in notRanked" :key="s.psgc" class="panel px-3 py-1.5 text-[0.8125rem]">
                                <b>{{ byPsgc.get(s.psgc)!.name }}</b>
                                <span class="text-muted"> in {{ byPsgc.get(s.psgc)!.province }}: {{ s.not_ranked_reason }}</span>
                            </li>
                        </ul>
                    </div>
                </section>
            </div>
        </div>
</template>
