<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const STEPS = [
    { route: 'trust', title: 'Data trust', text: 'Check the dataset before using it: matching, suppression and reconciliation.' },
    { route: 'map', title: 'Map', text: 'See participation across Caraga and open any municipality.' },
    { route: 'pipeline', title: 'Pipeline', text: 'Tell low applications apart from low exam attendance.' },
    { route: 'priority', title: 'Priority list', text: 'Rank municipalities with weights you can change.' },
    { route: 'planner', title: 'Planner', text: 'Choose how many places you can serve and record why.' },
    { route: 'brief', title: 'Brief and register', text: 'Print an evidence brief and keep the action register.' },
];
</script>

<template>
    <Head title="Start" />
    <AppLayout>
        <div class="max-w-3xl">
            <p class="label">NCE outreach · PSHS-CRC · Caraga</p>
            <h1 class="mt-1 text-3xl font-semibold">Where should outreach go next, and why?</h1>
            <p class="mt-3 text-muted">
                PULSE turns approved municipality summaries into a ranked outreach shortlist and an evidence brief. Work through the steps in order, or jump to any of them.
            </p>
        </div>
        <ol class="mt-8 grid gap-px border border-line bg-line sm:grid-cols-2 lg:grid-cols-3">
            <li v-for="(s, i) in STEPS" :key="s.route" class="bg-surface">
                <Link :href="route(s.route)" class="block h-full p-4 hover:bg-bg">
                    <span class="label">Step {{ i + 1 }}</span>
                    <span class="mt-1 block text-lg font-semibold">{{ s.title }}</span>
                    <span class="mt-1 block text-sm text-muted">{{ s.text }}</span>
                </Link>
            </li>
        </ol>
    </AppLayout>
</template>
