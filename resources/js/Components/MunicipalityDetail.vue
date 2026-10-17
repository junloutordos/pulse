<script setup lang="ts">
import CountUp from '@/Components/CountUp.vue';
import Funnel from '@/Components/Funnel.vue';
import StatusPill from '@/Components/StatusPill.vue';
import { count, fixed, pct, regionShare, signedPct } from '@/lib/format';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

export interface DetailRow {
    psgc: string;
    name: string;
    province: string;
    status: 'ok' | 'low' | 'suppressed' | 'missing';
    applicants: number | null;
    examinees: number | null;
    qualifiers: number | null;
    grade6_pop: number | null;
    prior_applicants: number | null;
    rate_per_1000: number | null;
    attendance: number | null;
    change: number | null;
}

const props = defineProps<{ row: DetailRow; all: DetailRow[]; rank: number | null; ranked: number; cycle: number; priorCycle: number | null }>();

const examinedRegion = computed(() => regionShare(props.all, 'examinees'));
const hidden = computed(() => props.row.status === 'suppressed' || props.row.status === 'missing');
const stages = computed(() => {
    const a = props.row.applicants;
    const share = (v: number | null) => (a && v !== null ? v / a : null);
    return [
        { label: 'Applied', value: a, share: a ? 1 : null, regionShare: 1 },
        { label: 'Examined', value: props.row.examinees, share: share(props.row.examinees), regionShare: examinedRegion.value },
        // Small qualifier cells are suppressed, so a region share would be built from partial data. Leave it out.
        { label: 'Qualified', value: props.row.qualifiers, share: share(props.row.qualifiers), regionShare: null },
    ];
});
const stats = computed(() => [
    { label: `Applicants in ${props.cycle}`, value: hidden.value ? null : props.row.applicants, fmt: (n: number) => count(Math.round(n)), sub: props.row.prior_applicants !== null ? `${count(props.row.prior_applicants)} in ${props.priorCycle ?? 'the prior cycle'}` : '' },
    { label: 'Applications per 1,000', value: props.row.rate_per_1000, fmt: (n: number) => fixed(n), sub: props.row.grade6_pop !== null ? `${count(props.row.grade6_pop)} Grade 6 learners` : 'No Grade 6 count' },
    { label: 'Exam attendance', value: props.row.attendance, fmt: (n: number) => pct(n), sub: 'of those who applied' },
    { label: `Change vs ${props.priorCycle ?? 'prior cycle'}`, value: props.row.change, fmt: (n: number) => signedPct(n), sub: 'in applications' },
]);
</script>

<template>
    <div class="grid gap-5">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <h3 class="text-2xl font-bold leading-tight">{{ row.name }}</h3>
                <p class="text-sm text-muted">{{ row.province }}</p>
            </div>
            <StatusPill :status="row.status" />
        </div>

        <p v-if="row.status === 'suppressed'" class="rounded bg-crit-bg px-3 py-2 text-sm text-crit">
            The applicant count is below 5, so PULSE withholds it. This municipality is not charted or ranked.
        </p>
        <p v-else-if="row.status === 'missing'" class="rounded bg-crit-bg px-3 py-2 text-sm text-crit">
            The source has no {{ cycle }} row for this municipality. Missing is not zero, so it is not charted or ranked.
        </p>
        <p v-else-if="row.status === 'low'" class="rounded bg-warn-bg px-3 py-2 text-sm text-warn">
            Fewer than 15 applicants. It is shown on the map but never ranked.
        </p>

        <div class="flex items-baseline gap-2">
            <CountUp class="font-display text-5xl font-bold leading-none" :value="rank" :format="(n) => `#${Math.round(n)}`" />
            <span class="text-sm text-muted">{{ rank ? `of ${ranked} ranked municipalities` : 'Not ranked' }}</span>
        </div>

        <dl class="grid grid-cols-2 gap-x-5 gap-y-4">
            <div v-for="s in stats" :key="s.label">
                <dt class="label">{{ s.label }}</dt>
                <dd class="font-display text-2xl font-bold leading-tight"><CountUp :value="s.value" :format="s.fmt" /></dd>
                <dd v-if="s.sub" class="text-[0.8125rem] text-muted">{{ s.sub }}</dd>
            </div>
        </dl>

        <div v-if="!hidden">
            <p class="label mb-2">How far applicants get</p>
            <Funnel :stages="stages" />
            <p class="mt-2 text-[0.8125rem] text-muted">Qualification reflects a selective exam. It is context, not evidence of access barriers.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link :href="route('brief', { psgc: row.psgc })" class="btn btn-primary">Open evidence brief</Link>
            <Link :href="route('pipeline', { psgc: row.psgc })" class="btn">See in pipeline</Link>
        </div>
    </div>
</template>
