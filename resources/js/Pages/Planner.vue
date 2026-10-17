<script setup lang="ts">
import MunicipalityMap from '@/Components/MunicipalityMap.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { suggestedFocus } from '@/lib/focus';
import { fixed } from '@/lib/format';
import { shortlist } from '@/lib/shortlist';
import { useThemeColors } from '@/lib/themeColors';
import { useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';

interface Decision { psgc: string; status: 'accepted' | 'deferred'; reason: string }
const props = defineProps<{
    rows: MunicipalityRow[];
    decisions: Decision[];
    register: { id: number; psgc: string; action: string; status: string }[];
    nearby: Record<string, { psgc: string; name: string; km: number }[]>;
    canPlan: boolean;
}>();

const colors = useThemeColors();
const { weights, scored } = useWeights(computed(() => props.rows));
const slots = ref(5);
const byPsgc = computed(() => new Map(props.rows.map((r) => [r.psgc, r])));
const list = computed(() => shortlist(scored.value, slots.value));
const rankable = computed(() => scored.value.filter((s) => s.rank !== null).length);
const decisionFor = (psgc: string) => props.decisions.find((d) => d.psgc === psgc);
const accepted = computed(() => props.decisions.filter((d) => d.status === 'accepted').length);

const REASONS = ['Matches local knowledge', 'Already served this cycle', 'Data looks off; verify first', 'Combine with neighbour trip'];
const reason = reactive<Record<string, string>>({});
const busy = ref<string | null>(null);

function decide(psgc: string, status: 'accepted' | 'deferred', action: string) {
    busy.value = psgc;
    router.post(
        route('planner.decide'),
        { psgc, status, reason: reason[psgc] ?? REASONS[0], action: status === 'accepted' ? action : null, weights: { ...weights } },
        { preserveScroll: true, onFinish: () => (busy.value = null) },
    );
}

const fills = computed<Record<string, string | null>>(() =>
    Object.fromEntries(
        props.rows.map((r) => {
            const s = scored.value.find((x) => x.psgc === r.psgc);
            if (!s || s.score === null) return [r.psgc, null];
            const bin = Math.min(4, Math.floor(s.score / 20));
            return [r.psgc, colors.value[`q${bin}` as 'q0']];
        }),
    ),
);
const highlight = computed(() => list.value.picks.map((p) => p.psgc));
const labels = computed(() => Object.fromEntries(scored.value.map((s) => [s.psgc, `${byPsgc.value.get(s.psgc)!.name}: ${s.score === null ? 'not ranked' : `score ${fixed(s.score, 0)}`}`])));
</script>

<template>
    <Head title="Outreach planner" />
    <AppLayout>
        <div class="grid gap-6">
            <div class="max-w-3xl">
                <p class="label">Step 5 · Decide</p>
                <h1 class="mt-1 text-3xl font-semibold">How many municipalities can we serve this cycle?</h1>
                <p class="mt-2 text-muted">PULSE suggests a shortlist from the current weights. Staff accept or defer each one and record why.</p>
            </div>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)]">
                <aside class="grid gap-4">
                    <div class="panel p-4">
                        <label for="slots" class="flex items-baseline justify-between text-sm font-semibold"><span>Outreach slots</span><span class="num">{{ slots }}</span></label>
                        <input id="slots" v-model.number="slots" type="range" min="1" max="12" class="mt-2 w-full accent-[var(--accent)]" />
                        <p v-if="list.capped" role="status" class="mt-2 text-sm text-warn">Only {{ rankable }} municipalities can be ranked, so the shortlist stops there.</p>
                    </div>
                    <MunicipalityMap :fills="fills" :highlight="highlight" :labels="labels" height-class="h-[320px] lg:h-[420px]" @select="() => {}" />
                    <p class="text-xs text-muted">Outlined municipalities are the suggested shortlist. Shading is the priority score at the current weights.</p>
                </aside>

                <section class="panel p-4" aria-labelledby="shortlist">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="shortlist" class="text-lg font-semibold">Suggested shortlist</h2>
                        <span class="pill pill-plain">{{ accepted }} accepted · {{ register.length }} in register</span>
                    </div>
                    <p v-if="!canPlan" class="mt-2 text-sm text-muted">You can view the shortlist. Planners record decisions.</p>
                    <ol class="mt-2 divide-y divide-line">
                        <li v-for="(p, i) in list.picks" :key="p.psgc" class="grid gap-3 py-3 sm:grid-cols-[2rem_minmax(0,1fr)_auto]">
                            <span class="num text-lg text-accent">{{ i + 1 }}</span>
                            <div class="min-w-0">
                                <p>
                                    <Link :href="route('brief', { psgc: p.psgc })" class="font-semibold hover:underline">{{ byPsgc.get(p.psgc)!.name }}</Link>
                                    <span class="text-sm text-muted"> · {{ byPsgc.get(p.psgc)!.province }} · score {{ fixed(p.score, 0) }}</span>
                                </p>
                                <p class="text-sm">{{ suggestedFocus(p, weights) }}</p>
                                <p v-if="nearby[p.psgc]?.length" class="text-xs text-muted">
                                    Could be grouped with: {{ nearby[p.psgc].map((n) => `${n.name} (${n.km} km)`).join(', ') }}. Straight-line distance, context only.
                                </p>
                                <p v-if="decisionFor(p.psgc)" class="mt-1 text-xs">
                                    <span class="pill" :class="decisionFor(p.psgc)!.status === 'accepted' ? 'pill-ok' : 'pill-warn'">{{ decisionFor(p.psgc)!.status === 'accepted' ? 'Accepted' : 'Deferred' }}</span>
                                    <span class="text-muted"> {{ decisionFor(p.psgc)!.reason }}</span>
                                </p>
                            </div>
                            <div v-if="canPlan" class="grid content-start gap-2">
                                <label class="sr-only" :for="`why-${p.psgc}`">Reason for {{ byPsgc.get(p.psgc)!.name }}</label>
                                <select :id="`why-${p.psgc}`" :value="reason[p.psgc] ?? REASONS[0]" class="py-1 text-xs" @change="reason[p.psgc] = ($event.target as HTMLSelectElement).value">
                                    <option v-for="r in REASONS" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-primary !px-3 !py-1 text-xs" :disabled="busy === p.psgc" @click="decide(p.psgc, 'accepted', suggestedFocus(p, weights))">Accept</button>
                                    <button type="button" class="btn !px-3 !py-1 text-xs" :disabled="busy === p.psgc" @click="decide(p.psgc, 'deferred', '')">Defer</button>
                                </div>
                            </div>
                        </li>
                    </ol>
                    <Link :href="route('brief')" class="btn mt-3">Go to briefs and register</Link>
                </section>
            </div>
        </div>
    </AppLayout>
</template>
