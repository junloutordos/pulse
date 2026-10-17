<script setup lang="ts">
import LineageStrip from '@/Components/LineageStrip.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { count } from '@/lib/format';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

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

const stats = computed(() => [
    { label: 'Source rows matched', value: `${count(props.counts.matched)}/${count(props.counts.matched + props.counts.unmatched)}`, tone: props.counts.unmatched ? 'text-crit' : 'text-ok' },
    { label: 'Name fixes', value: count(props.counts.aliasFixes), tone: '' },
    { label: 'Shared names resolved', value: count(props.counts.sharedNames), tone: '' },
    { label: 'Low sample', value: count(props.counts.low), tone: props.counts.low ? 'text-warn' : '' },
    { label: 'Suppressed', value: count(props.counts.suppressed), tone: props.counts.suppressed ? 'text-crit' : '' },
    { label: 'Missing this cycle', value: count(props.counts.missing), tone: props.counts.missing ? 'text-crit' : '' },
]);

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
</script>

<template>
    <Head title="Data trust" />
    <AppLayout>
        <div class="grid gap-8">
            <div class="max-w-3xl">
                <p class="label">Step 1 · Before any map</p>
                <h1 class="mt-1 text-3xl font-semibold">Can we trust this dataset?</h1>
                <p class="mt-2 text-muted">
                    Every municipality is matched to its PSGC code and every total is checked against the custodian's approved figures before anything is mapped or ranked.
                </p>
            </div>

            <dl class="grid grid-cols-2 gap-px border border-line bg-line sm:grid-cols-3 lg:grid-cols-6">
                <div v-for="s in stats" :key="s.label" class="bg-surface px-4 py-3">
                    <dt class="label">{{ s.label }}</dt>
                    <dd class="num mt-1 text-2xl" :class="s.tone">{{ s.value }}</dd>
                </div>
            </dl>

            <section aria-labelledby="lineage">
                <h2 id="lineage" class="mb-3 text-lg font-semibold">Where every number comes from</h2>
                <LineageStrip :steps="lineage" />
            </section>

            <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_20rem]">
                <section aria-labelledby="recon">
                    <h2 id="recon" class="mb-3 text-lg font-semibold">Reconciliation with approved totals</h2>
                    <div v-if="reconciliation.length" class="panel overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="border-b border-line text-left">
                                    <th class="label px-3 py-2">Scope, {{ latestCycle }} cycle</th>
                                    <th class="label px-3 py-2 text-right">Applicants</th><th class="label px-3 py-2 text-right">Custodian</th>
                                    <th class="label px-3 py-2 text-right">Examinees</th><th class="label px-3 py-2 text-right">Custodian</th>
                                    <th class="label px-3 py-2">Check</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in pivot" :key="r.scope" class="border-b border-line last:border-0">
                                    <td class="px-3 py-2" :class="r.scope === 'Caraga' ? 'font-semibold' : ''">{{ r.scope }}</td>
                                    <td class="num px-3 py-2 text-right">{{ count(r.applicants?.pulse_sum) }}</td>
                                    <td class="num px-3 py-2 text-right text-muted">{{ count(r.applicants?.custodian_sum) }}</td>
                                    <td class="num px-3 py-2 text-right">{{ count(r.examinees?.pulse_sum) }}</td>
                                    <td class="num px-3 py-2 text-right text-muted">{{ count(r.examinees?.custodian_sum) }}</td>
                                    <td class="px-3 py-2"><span class="pill" :class="r.ok ? 'pill-ok' : 'pill-crit'">{{ r.ok ? 'Matches' : 'Differs' }}</span></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <p v-else class="panel p-4 text-sm text-muted">No custodian totals were provided with this dataset, so the totals could not be reconciled.</p>
                    <p v-if="reconciliation.length" class="mt-2 text-sm" :class="allMatch ? 'text-ok' : 'text-crit'">
                        {{ allMatch ? 'Every total reconciles with the custodian.' : 'Some totals differ from the custodian. Resolve them before planning.' }}
                    </p>
                </section>

                <section aria-labelledby="rules">
                    <h2 id="rules" class="mb-3 text-lg font-semibold">Rules applied</h2>
                    <ul class="panel grid gap-2 p-4 text-sm">
                        <li>Missing values are never treated as zero.</li>
                        <li>Counts below 5 are withheld, not mapped, not ranked.</li>
                        <li>Fewer than 15 applicants: mapped, never ranked.</li>
                        <li>Qualification is context, not evidence of access barriers.</li>
                        <li>Distance and adjacency are context only.</li>
                    </ul>
                </section>
            </div>

            <section aria-labelledby="issues">
                <h2 id="issues" class="mb-3 text-lg font-semibold">Records that needed attention</h2>
                <div class="panel overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line text-left">
                                <th class="label px-3 py-2">Municipality</th><th class="label px-3 py-2">Province</th>
                                <th class="label px-3 py-2">What happened</th><th class="label px-3 py-2">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="(i, n) in issues" :key="`${i.psgc}-${i.kind}-${n}`" class="border-b border-line last:border-0">
                                <td class="px-3 py-2 font-semibold">
                                    <Link v-if="i.psgc" :href="route('map', { psgc: i.psgc })" class="hover:underline">{{ i.name }}</Link>
                                    <template v-else>{{ i.name }}</template>
                                </td>
                                <td class="px-3 py-2 text-muted">{{ i.province }}</td>
                                <td class="px-3 py-2">{{ i.reason }}</td>
                                <td class="px-3 py-2"><span class="pill" :class="KIND[i.kind]?.tone">{{ KIND[i.kind]?.label ?? i.kind }}</span></td>
                            </tr>
                            <tr v-if="!issues.length"><td colspan="4" class="px-3 py-4 text-muted">No records needed attention.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
