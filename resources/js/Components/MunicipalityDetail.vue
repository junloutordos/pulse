<script setup lang="ts">
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
</script>

<template>
    <div class="grid gap-4">
        <div class="flex items-start justify-between gap-3">
            <div>
                <h3 class="text-xl font-semibold">{{ row.name }}</h3>
                <p class="text-sm text-muted">{{ row.province }}</p>
            </div>
            <StatusPill :status="row.status" />
        </div>

        <p v-if="row.status === 'suppressed'" class="border-l-2 border-crit bg-crit-bg px-3 py-2 text-sm text-crit">
            The applicant count is below 5, so PULSE withholds it. This municipality is not charted or ranked.
        </p>
        <p v-else-if="row.status === 'missing'" class="border-l-2 border-crit bg-crit-bg px-3 py-2 text-sm text-crit">
            The source has no {{ cycle }} row for this municipality. Missing is not zero, so it is not charted or ranked.
        </p>
        <p v-else-if="row.status === 'low'" class="border-l-2 border-warn bg-warn-bg px-3 py-2 text-sm text-warn">
            Fewer than 15 applicants. It is shown on the map but never ranked.
        </p>

        <dl class="grid grid-cols-[1fr_auto] gap-x-6 gap-y-1.5 text-sm">
            <dt class="text-muted">Applicants {{ cycle }}</dt><dd class="num text-right">{{ hidden ? '—' : count(row.applicants) }}</dd>
            <dt class="text-muted">Applicants {{ priorCycle ?? 'prior' }}</dt><dd class="num text-right">{{ count(row.prior_applicants) }}</dd>
            <dt class="text-muted">Grade 6 learners</dt><dd class="num text-right">{{ count(row.grade6_pop) }}</dd>
            <dt class="text-muted">Applications per 1,000</dt><dd class="num text-right">{{ fixed(row.rate_per_1000) }}</dd>
            <dt class="text-muted">Exam attendance</dt><dd class="num text-right">{{ pct(row.attendance) }}</dd>
            <dt class="text-muted">Change vs {{ priorCycle ?? 'prior cycle' }}</dt><dd class="num text-right">{{ signedPct(row.change) }}</dd>
            <dt class="text-muted">Priority rank</dt>
            <dd class="num text-right">{{ rank ? `#${rank} of ${ranked}` : 'Not ranked' }}</dd>
        </dl>

        <div v-if="!hidden">
            <p class="label mb-2">Pipeline stages, share of applicants</p>
            <Funnel :stages="stages" />
            <p class="mt-2 text-xs text-muted">Qualification reflects a selective exam. It is context, not evidence of access barriers.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Link :href="route('brief', { psgc: row.psgc })" class="btn btn-primary">Open evidence brief</Link>
            <Link :href="route('pipeline', { psgc: row.psgc })" class="btn">See in pipeline</Link>
        </div>
    </div>
</template>
