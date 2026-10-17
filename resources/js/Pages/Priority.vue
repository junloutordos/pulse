<script setup lang="ts">
import DataNotices from '@/Components/DataNotices.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { fixed, pct, signedPct } from '@/lib/format';
import { suggestedFocus } from '@/lib/focus';
import { PRESETS, rankBand } from '@/lib/rankBand';
import { useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps<{ rows: MunicipalityRow[]; canPlan: boolean }>();
const { weights, message, scored, available, update, applyPreset } = useWeights(computed(() => props.rows));

const byPsgc = computed(() => new Map(props.rows.map((r) => [r.psgc, r])));
const ranked = computed(() => scored.value.filter((s) => s.rank !== null).sort((a, b) => a.rank! - b.rank!));
const notRanked = computed(() => scored.value.filter((s) => s.rank === null));
const weightSum = computed(() => {
    const s = scored.value.find((r) => r.score !== null);
    return (['reach', 'attendance', 'decline'] as const).reduce((sum, k) => sum + (s && s[`c_${k}`] !== null ? weights[k] : 0), 0) || 1;
});

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
</script>

<template>
    <Head title="Priority list" />
    <AppLayout>
        <div class="grid gap-6">
            <div class="max-w-3xl">
                <p class="label">Step 4 · Who first</p>
                <h1 class="mt-1 text-3xl font-semibold">Explainable outreach priority</h1>
                <p class="mt-2 text-muted">Weights are planning preferences, not predictions. Move them and watch which places keep their rank.</p>
            </div>
            <DataNotices :rows="rows" />

            <div class="grid items-start gap-6 xl:grid-cols-[18rem_minmax(0,1fr)]">
                <aside class="panel grid gap-4 p-4">
                    <h2 class="text-lg font-semibold">Weights</h2>
                    <div v-for="s in SLIDERS" :key="s.key">
                        <label class="flex items-baseline justify-between text-sm" :for="`w-${s.key}`">
                            <span>{{ s.label }}</span>
                            <span class="num text-muted">{{ Math.round(weights[s.key] * 100) }}</span>
                        </label>
                        <input
                            :id="`w-${s.key}`"
                            type="range" min="0" max="100" step="1"
                            :value="Math.round(weights[s.key] * 100)"
                            :disabled="!active(s.key)"
                            class="mt-1 w-full accent-[var(--accent)] disabled:opacity-40"
                            @input="onSlide(s.key, $event.target as HTMLInputElement)"
                        />
                        <p v-if="!active(s.key)" class="text-xs text-muted">Not available in this dataset.</p>
                    </div>
                    <p v-if="message" role="alert" class="border-l-2 border-warn bg-warn-bg px-3 py-2 text-sm text-warn">{{ message }}</p>
                    <div class="flex flex-wrap gap-2">
                        <button v-for="name in Object.keys(PRESETS)" :key="name" type="button" class="btn !px-2.5 !py-1 text-xs" @click="applyPreset(name)">{{ name }}</button>
                    </div>
                    <ul class="grid gap-1 text-xs text-muted" aria-label="Score components">
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 bg-q4" />Low applications</li>
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 bg-q2" />Low attendance</li>
                        <li class="flex items-center gap-2"><i class="inline-block h-2.5 w-5 bg-q1" />Decline</li>
                    </ul>
                    <p class="text-xs text-muted"><b>Stable</b> means the rank stays within a band of four across all three presets and within the top 10.</p>
                </aside>

                <section class="grid gap-4" aria-labelledby="ranked">
                    <div class="panel overflow-x-auto">
                        <table class="w-full text-sm">
                            <caption id="ranked" class="sr-only">Municipalities ranked by priority score</caption>
                            <thead>
                                <tr class="border-b border-line text-left">
                                    <th class="label px-3 py-2 text-right">#</th><th class="label px-3 py-2">Municipality</th><th class="label px-3 py-2">Score</th>
                                    <th class="label px-3 py-2 text-right">Apps/1k</th><th class="label px-3 py-2 text-right">Attend</th><th class="label whitespace-nowrap px-3 py-2 text-right">Δ prior</th>
                                    <th class="label px-3 py-2">Rank band</th><th class="label px-3 py-2">Suggested focus</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="s in ranked" :key="s.psgc" class="border-b border-line last:border-0">
                                    <td class="num px-3 py-2 text-right">{{ s.rank }}</td>
                                    <td class="px-3 py-2">
                                        <Link :href="route('map', { psgc: s.psgc })" class="font-semibold hover:underline">{{ byPsgc.get(s.psgc)!.name }}</Link>
                                        <div class="text-xs text-muted">{{ byPsgc.get(s.psgc)!.province }}</div>
                                    </td>
                                    <td class="px-3 py-2">
                                        <div class="flex items-center gap-2">
                                            <span class="num w-8 text-right">{{ fixed(s.score, 0) }}</span>
                                            <div class="flex h-2 w-28 bg-bg" role="img" :aria-label="`Score ${fixed(s.score, 0)}: applications ${fixed(segment(s.c_reach, 'reach'), 0)}, attendance ${fixed(segment(s.c_attendance, 'attendance'), 0)}, decline ${fixed(segment(s.c_decline, 'decline'), 0)}`">
                                                <span class="bg-q4" :style="{ width: `${segment(s.c_reach, 'reach')}%` }" />
                                                <span class="bg-q2" :style="{ width: `${segment(s.c_attendance, 'attendance')}%` }" />
                                                <span class="bg-q1" :style="{ width: `${segment(s.c_decline, 'decline')}%` }" />
                                            </div>
                                        </div>
                                    </td>
                                    <td class="num px-3 py-2 text-right">{{ fixed(byPsgc.get(s.psgc)!.rate_per_1000) }}</td>
                                    <td class="num px-3 py-2 text-right">{{ pct(byPsgc.get(s.psgc)!.attendance) }}</td>
                                    <td class="num px-3 py-2 text-right">{{ signedPct(byPsgc.get(s.psgc)!.change) }}</td>
                                    <td class="whitespace-nowrap px-3 py-2">
                                        <template v-if="bands.get(s.psgc)">
                                            <span class="pill" :class="bands.get(s.psgc)!.stable ? 'pill-ok' : 'pill-warn'">{{ bands.get(s.psgc)!.stable ? 'Stable' : 'Sensitive' }}</span>
                                            <span class="num ml-1 text-xs text-muted">#{{ bands.get(s.psgc)!.lo }}–{{ bands.get(s.psgc)!.hi }}</span>
                                        </template>
                                    </td>
                                    <td class="px-3 py-2 text-xs">{{ suggestedFocus(s, weights) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div>
                        <h2 class="mb-2 text-lg font-semibold">Not ranked ({{ notRanked.length }})</h2>
                        <ul class="flex flex-wrap gap-2">
                            <li v-for="s in notRanked" :key="s.psgc" class="border border-line bg-surface px-2 py-1 text-xs">
                                <b>{{ byPsgc.get(s.psgc)!.name }}</b>
                                <span class="text-muted"> · {{ s.not_ranked_reason }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('planner')" class="btn btn-primary">Plan outreach with these weights</Link>
                    </div>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
