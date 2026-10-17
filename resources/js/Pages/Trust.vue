<script setup lang="ts">
import LineageStrip from '@/Components/LineageStrip.vue';
import PageHeader from '@/Components/PageHeader.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { count } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import { CircleAlert, CircleCheck } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Issue { psgc: string | null; name: string; province: string; kind: string; reason: string }
const props = defineProps<{
    counts: { total: number; matched: number; unmatched: number; aliasFixes: number; sharedNames: number; low: number; suppressed: number; missing: number };
    lineage: { label: string; detail: string; ref: string }[];
    reconciliation: { scope: string; cycle: number; metric: string; pulse_sum: number; custodian_sum: number; matches: boolean }[];
    issues: Issue[];
}>();

const KIND: Record<string, { label: string; tone: string }> = {
    unmatched: { label: 'Not matched', tone: 'pill-crit' },
    duplicate: { label: 'Duplicate row', tone: 'pill-crit' },
    suppressed: { label: 'Suppressed', tone: 'pill-crit' },
    missing: { label: 'Missing', tone: 'pill-crit' },
    low: { label: 'Low sample', tone: 'pill-warn' },
    shared_name: { label: 'Shared name', tone: 'pill-warn' },
    alias: { label: 'Name fix', tone: 'pill-plain' },
};

const latestCycle = computed(() => Math.max(...props.reconciliation.map((r) => r.cycle)));
const pivot = computed(() => {
    const scopes = [...new Set(props.reconciliation.filter((r) => r.cycle === latestCycle.value).map((r) => r.scope))];
    return scopes.map((scope) => {
        const own = props.reconciliation.filter((r) => r.scope === scope && r.cycle === latestCycle.value);
        const by = (metric: string) => own.find((r) => r.metric === metric);
        return { scope, applicants: by('applicants'), examinees: by('examinees'), ok: own.every((r) => r.matches) };
    });
});
const allMatch = computed(() => props.reconciliation.length > 0 && props.reconciliation.every((r) => r.matches));

const needsAttention = computed(() => props.counts.unmatched + props.counts.suppressed + props.counts.missing);
const ready = computed(() => allMatch.value && props.counts.unmatched === 0);

const filter = ref<string>('all');
const kinds = computed(() => [...new Set(props.issues.map((i) => i.kind))]);
const shown = computed(() => (filter.value === 'all' ? props.issues : props.issues.filter((i) => i.kind === filter.value)));
const countOf = (k: string) => props.issues.filter((i) => i.kind === k).length;

const FACTS = computed(() => [
    { label: 'Matched to PSGC', value: `${count(props.counts.matched)} of ${count(props.counts.matched + props.counts.unmatched)}` },
    { label: 'Name fixes', value: count(props.counts.aliasFixes) },
    { label: 'Shared names resolved', value: count(props.counts.sharedNames) },
    { label: 'Low sample', value: count(props.counts.low) },
    { label: 'Suppressed', value: count(props.counts.suppressed) },
    { label: 'Missing this cycle', value: count(props.counts.missing) },
]);
const RULES = [
    'Missing values are never treated as zero.',
    'Counts below 5 are withheld, not mapped, not ranked.',
    'Fewer than 15 applicants: mapped, never ranked.',
    'Qualification is context, not evidence of access barriers.',
    'Distance and adjacency are context only.',
];
</script>

<template>
    <Head title="Data trust" />
    <AppLayout>
        <div class="grid gap-10">
            <PageHeader>
                <template #title>Can we trust this dataset?</template>
                Every municipality is matched to its PSGC code and every total is checked against the custodian's approved figures before anything is mapped or ranked.
            </PageHeader>

            <section class="panel grid gap-6 p-6 lg:grid-cols-[minmax(0,22rem)_minmax(0,1fr)] lg:items-center" aria-label="Verdict">
                <div class="flex items-start gap-4">
                    <span class="flex h-12 w-12 flex-none items-center justify-center rounded-full" :class="ready ? 'bg-ok-bg text-ok' : 'bg-warn-bg text-warn'">
                        <CircleCheck v-if="ready" class="h-6 w-6" aria-hidden="true" /><CircleAlert v-else class="h-6 w-6" aria-hidden="true" />
                    </span>
                    <div>
                        <h2 class="text-2xl font-bold leading-tight">{{ ready ? 'Ready for planning' : 'Needs attention first' }}</h2>
                        <p class="mt-1 text-sm text-muted">
                            {{ allMatch ? 'Every total reconciles with the custodian.' : 'Some totals differ from the custodian. Resolve them before planning.' }}
                            {{ needsAttention ? `${needsAttention} record${needsAttention === 1 ? '' : 's'} withheld or missing.` : '' }}
                        </p>
                    </div>
                </div>
                <dl class="grid grid-cols-2 gap-x-6 gap-y-4 sm:grid-cols-3">
                    <div v-for="f in FACTS" :key="f.label">
                        <dt class="label">{{ f.label }}</dt>
                        <dd class="num font-display text-2xl font-bold leading-tight">{{ f.value }}</dd>
                    </div>
                </dl>
            </section>

            <section aria-labelledby="lineage">
                <h2 id="lineage" class="mb-3 text-xl font-bold">Where every number comes from</h2>
                <LineageStrip :steps="lineage" />
            </section>

            <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section aria-labelledby="recon">
                    <h2 id="recon" class="mb-3 text-xl font-bold">Totals against the custodian, {{ latestCycle }} cycle</h2>
                    <div v-if="reconciliation.length" class="panel overflow-x-auto">
                        <table class="data-table">
                            <thead>
                                <tr><th>Scope</th><th class="text-right">Applicants</th><th class="text-right">Custodian</th><th class="text-right">Examinees</th><th class="text-right">Custodian</th><th>Check</th></tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in pivot" :key="r.scope">
                                    <td :class="r.scope === 'Caraga' ? 'font-semibold' : ''">{{ r.scope }}</td>
                                    <td class="num text-right">{{ count(r.applicants?.pulse_sum) }}</td>
                                    <td class="num text-right text-muted">{{ count(r.applicants?.custodian_sum) }}</td>
                                    <td class="num text-right">{{ count(r.examinees?.pulse_sum) }}</td>
                                    <td class="num text-right text-muted">{{ count(r.examinees?.custodian_sum) }}</td>
                                    <td><span class="pill" :class="r.ok ? 'pill-ok' : 'pill-crit'">{{ r.ok ? 'Matches' : 'Differs' }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="panel p-4 text-sm text-muted">No custodian totals were provided with this dataset, so the totals could not be reconciled.</p>
                </section>

                <section aria-labelledby="rules">
                    <h2 id="rules" class="mb-3 text-xl font-bold">Rules applied</h2>
                    <ul class="panel grid gap-3 p-5 text-sm">
                        <li v-for="r in RULES" :key="r" class="flex gap-2"><CircleCheck class="mt-0.5 h-4 w-4 flex-none text-ok" aria-hidden="true" />{{ r }}</li>
                    </ul>
                </section>
            </div>

            <section aria-labelledby="issues">
                <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                    <h2 id="issues" class="text-xl font-bold">Records that needed attention</h2>
                    <div class="flex flex-wrap gap-1.5" role="group" aria-label="Filter records">
                        <button type="button" class="pill" :class="filter === 'all' ? 'bg-ink text-bg' : 'pill-plain'" :aria-pressed="filter === 'all'" @click="filter = 'all'">All {{ issues.length }}</button>
                        <button v-for="k in kinds" :key="k" type="button" class="pill" :class="filter === k ? 'bg-ink text-bg' : 'pill-plain'" :aria-pressed="filter === k" @click="filter = k">{{ KIND[k]?.label ?? k }} {{ countOf(k) }}</button>
                    </div>
                </div>
                <div class="panel overflow-x-auto">
                    <table class="data-table">
                        <thead><tr><th>Municipality</th><th>Province</th><th>What happened</th><th>Status</th></tr></thead>
                        <tbody>
                            <tr v-for="(i, n) in shown" :key="`${i.psgc}-${i.kind}-${n}`">
                                <td class="font-semibold">
                                    <Link v-if="i.psgc" :href="route('map', { psgc: i.psgc })" class="hover:underline">{{ i.name }}</Link>
                                    <template v-else>{{ i.name }}</template>
                                </td>
                                <td class="text-muted">{{ i.province }}</td>
                                <td>{{ i.reason }}</td>
                                <td><span class="pill" :class="KIND[i.kind]?.tone">{{ KIND[i.kind]?.label ?? i.kind }}</span></td>
                            </tr>
                            <tr v-if="!shown.length"><td colspan="4" class="text-muted">No records in this group.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
