<script setup lang="ts">
import MunicipalityMap, { MapTip } from '@/Components/MunicipalityMap.vue';
import PageHeader from '@/Components/PageHeader.vue';
import { suggestedFocus } from '@/lib/focus';
import { fixed } from '@/lib/format';
import { shortlist } from '@/lib/shortlist';
import { useThemeColors } from '@/lib/themeColors';
import { notify } from '@/lib/toasts';
import { useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
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
const filled = computed(() => list.value.picks.filter((p) => decisionFor(p.psgc)?.status === 'accepted').length);

const REASONS = ['Matches local knowledge', 'Already served this cycle', 'Data looks off; verify first', 'Combine with neighbour trip'];
const reason = reactive<Record<string, string>>({});
const busy = ref<string | null>(null);

const firstError = (errors: Record<string, string>) => Object.values(errors)[0] ?? 'Something went wrong. Try again.';

function post(payload: Record<string, string | number | null | Record<string, number>>, onSuccess: () => void) {
    router.post(route('planner.decide'), payload, {
        preserveScroll: true,
        onSuccess,
        onError: (errors) => notify({ message: firstError(errors), tone: 'error' }),
        onFinish: () => (busy.value = null),
    });
}

/** Puts a municipality back the way it was before the last choice: either nothing, or the earlier decision. */
function undo(psgc: string, before: (Decision & { weights?: Record<string, number> }) | undefined, beforeAction: string | null) {
    const done = () => notify({ message: 'Undone' });
    if (!before) {
        router.delete(route('planner.undo', psgc), { preserveScroll: true, onSuccess: done, onError: (e) => notify({ message: firstError(e), tone: 'error' }) });
        return;
    }
    post({ psgc, status: before.status, reason: before.reason, action: before.status === 'accepted' ? beforeAction : null, weights: before.weights ?? { ...weights } }, done);
}

function decide(psgc: string, status: 'accepted' | 'deferred', action: string) {
    busy.value = psgc;
    const before = props.decisions.find((d) => d.psgc === psgc) as (Decision & { weights?: Record<string, number> }) | undefined;
    const beforeAction = props.register.find((r) => r.psgc === psgc)?.action ?? null;
    const name = byPsgc.value.get(psgc)!.name;
    post(
        { psgc, status, reason: reason[psgc] ?? REASONS[0], action: status === 'accepted' ? action : null, weights: { ...weights } },
        () => notify({ message: `${status === 'accepted' ? 'Accepted' : 'Deferred'} ${name}`, action: { label: 'Undo', run: () => undo(psgc, before, beforeAction) } }),
    );
}

const fills = computed<Record<string, string | null>>(() =>
    Object.fromEntries(
        props.rows.map((r) => {
            const s = scored.value.find((x) => x.psgc === r.psgc);
            if (!s || s.score === null) return [r.psgc, null];
            return [r.psgc, colors.value[`q${Math.min(4, Math.floor(s.score / 20))}` as 'q0']];
        }),
    ),
);
const highlight = computed(() => list.value.picks.map((p) => p.psgc));
const tips = computed<Record<string, MapTip>>(() =>
    Object.fromEntries(scored.value.map((s) => {
        const r = byPsgc.value.get(s.psgc)!;
        return [s.psgc, { title: `${r.name}, ${r.province}`, value: s.score === null ? 'Not ranked' : `Score ${fixed(s.score, 0)}`, detail: s.rank ? `Priority #${s.rank}` : s.not_ranked_reason ?? '' }];
    })),
);
</script>

<template>
    <Head title="Outreach planner" />
        <div class="grid gap-8">
            <PageHeader>
                <template #title>How many municipalities can we serve this cycle?</template>
                PULSE suggests a shortlist from the current weights. Staff accept or defer each one and record why.
            </PageHeader>

            <div class="grid items-start gap-6 lg:grid-cols-[minmax(0,24rem)_minmax(0,1fr)]">
                <aside class="grid gap-4 lg:sticky lg:top-36">
                    <div class="panel p-5">
                        <label for="slots" class="flex items-baseline justify-between text-sm font-medium"><span>Outreach slots</span><span class="num font-display text-3xl font-bold leading-none">{{ slots }}</span></label>
                        <input id="slots" v-model.number="slots" type="range" min="1" max="25" class="range mt-4" :style="{ '--pct': `${((slots - 1) / 24) * 100}%` }" />
                        <p v-if="list.capped" role="status" class="mt-3 text-sm text-warn">Only {{ rankable }} municipalities can be ranked, so the shortlist stops there.</p>
                        <ul class="mt-4 flex flex-wrap gap-1.5" :aria-label="`${filled} of ${list.picks.length} slots filled`">
                            <li v-for="(p, i) in list.picks" :key="p.psgc" class="h-2 min-w-6 flex-1 rounded-full" :class="decisionFor(p.psgc)?.status === 'accepted' ? 'bg-accent' : decisionFor(p.psgc) ? 'bg-warn' : 'bg-surface-2'" :title="`${i + 1}. ${byPsgc.get(p.psgc)!.name}`" />
                        </ul>
                        <p class="mt-2 text-[0.8125rem] text-muted">{{ filled }} of {{ list.picks.length }} slots filled</p>
                    </div>
                    <div class="panel h-[22rem] overflow-hidden lg:h-[26rem]">
                        <MunicipalityMap shape="boundary" :fills="fills" :highlight="highlight" :tips="tips" :padding="{ top: 24, bottom: 24, left: 24, right: 24 }" :show-labels="false" />
                    </div>
                    <p class="text-[0.8125rem] text-muted">Outlined hexagons are the suggested shortlist. Shading is the priority score at the current weights.</p>
                </aside>

                <section class="panel p-5" aria-labelledby="shortlist">
                    <div class="flex flex-wrap items-center justify-between gap-2">
                        <h2 id="shortlist" class="text-lg font-bold">Suggested shortlist</h2>
                        <span class="pill pill-plain">{{ accepted }} accepted, {{ register.length }} in register</span>
                    </div>
                    <p v-if="!canPlan" class="mt-2 text-sm text-muted">You can view the shortlist. Planners record decisions.</p>
                    <ol class="mt-3 divide-y divide-line/70">
                        <li v-for="(p, i) in list.picks" :key="p.psgc" class="grid gap-3 py-4 sm:grid-cols-[2.5rem_minmax(0,1fr)_auto]">
                            <span class="num font-display text-3xl font-bold leading-none text-accent">{{ i + 1 }}</span>
                            <div class="min-w-0">
                                <p class="flex flex-wrap items-baseline gap-x-2">
                                    <Link :href="route('brief', { psgc: p.psgc })" class="text-lg font-semibold hover:underline">{{ byPsgc.get(p.psgc)!.name }}</Link>
                                    <span class="text-sm text-muted">{{ byPsgc.get(p.psgc)!.province }}, score {{ fixed(p.score, 0) }}</span>
                                </p>
                                <p class="text-sm">{{ suggestedFocus(p, weights) }}</p>
                                <p v-if="nearby[p.psgc]?.length" class="mt-0.5 text-[0.8125rem] text-muted">
                                    Could be grouped with {{ nearby[p.psgc].map((n) => `${n.name} (${n.km} km)`).join(', ') }}. Straight-line distance, context only.
                                </p>
                                <p v-if="decisionFor(p.psgc)" class="mt-1.5 text-[0.8125rem]">
                                    <span class="pill" :class="decisionFor(p.psgc)!.status === 'accepted' ? 'pill-ok' : 'pill-warn'">{{ decisionFor(p.psgc)!.status === 'accepted' ? 'Accepted' : 'Deferred' }}</span>
                                    <span class="text-muted"> {{ decisionFor(p.psgc)!.reason }}</span>
                                </p>
                            </div>
                            <div v-if="canPlan" class="grid content-start gap-2">
                                <label class="sr-only" :for="`why-${p.psgc}`">Reason for {{ byPsgc.get(p.psgc)!.name }}</label>
                                <select :id="`why-${p.psgc}`" :value="reason[p.psgc] ?? REASONS[0]" class="h-8 rounded border-0 bg-surface-2 py-0 text-[0.8125rem]" @change="reason[p.psgc] = ($event.target as HTMLSelectElement).value">
                                    <option v-for="r in REASONS" :key="r" :value="r">{{ r }}</option>
                                </select>
                                <div class="flex gap-2">
                                    <button type="button" class="btn btn-primary btn-sm" :disabled="busy === p.psgc" @click="decide(p.psgc, 'accepted', suggestedFocus(p, weights))">Accept</button>
                                    <button type="button" class="btn btn-sm" :disabled="busy === p.psgc" @click="decide(p.psgc, 'deferred', '')">Defer</button>
                                </div>
                            </div>
                        </li>
                    </ol>
                    <Link :href="route('brief')" class="btn mt-3">Go to briefs and register <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>
                </section>
            </div>
        </div>
</template>
