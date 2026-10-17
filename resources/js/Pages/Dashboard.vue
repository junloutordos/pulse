<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import { PageProps } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { ArrowRight } from 'lucide-vue-next';
import { computed } from 'vue';

const dataset = computed(() => usePage<PageProps>().props.dataset);

const STEPS = [
    { route: 'trust', title: 'Check the data', text: 'See how the dataset was matched, what was withheld and whether the totals reconcile.' },
    { route: 'map', title: 'See where', text: 'Explore participation across Caraga and open any municipality.' },
    { route: 'pipeline', title: 'Find the stage', text: 'Tell low applications apart from low exam attendance.' },
    { route: 'priority', title: 'Rank municipalities', text: 'Set weights and watch which places keep their rank.' },
    { route: 'planner', title: 'Decide', text: 'Choose how many municipalities you can serve and record why.' },
    { route: 'brief', title: 'Write it up', text: 'Print an evidence brief and keep the action register.' },
];
const refreshed = computed(() => (dataset.value ? new Date(dataset.value.refreshedAt).toLocaleDateString('en-PH', { day: 'numeric', month: 'long', year: 'numeric' }) : ''));
</script>

<template>
    <Head title="Start" />
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1fr)_22rem]">
            <div class="grid content-start gap-8">
                <PageHeader>
                    <template #title>Where should NCE outreach go next, and why?</template>
                    PULSE turns approved municipality summaries into a ranked shortlist and an evidence brief for PSHS-CRC. Work through the steps in order, or jump to any of them.
                </PageHeader>
                <Link :href="route('trust')" class="btn btn-primary h-11 w-fit px-5 text-base">Start with the data check <ArrowRight class="h-4 w-4" aria-hidden="true" /></Link>

                <ol class="relative grid gap-1 before:absolute before:bottom-6 before:left-[1.1rem] before:top-6 before:w-px before:bg-line" aria-label="Steps">
                    <li v-for="(s, i) in STEPS" :key="s.route">
                        <Link :href="route(s.route)" class="group relative flex gap-4 rounded-md p-3 transition-colors hover:bg-surface">
                            <span class="num relative z-10 flex h-8 w-8 flex-none items-center justify-center rounded-full bg-surface-2 text-sm font-bold ring-4 ring-bg group-hover:bg-accent group-hover:text-accent-ink">{{ i + 1 }}</span>
                            <span>
                                <span class="block font-display text-lg font-semibold leading-tight">{{ s.title }}</span>
                                <span class="block text-sm text-muted">{{ s.text }}</span>
                            </span>
                        </Link>
                    </li>
                </ol>
            </div>

            <aside class="panel h-fit p-5">
                <h2 class="text-lg font-bold">Active dataset</h2>
                <dl v-if="dataset" class="mt-3 grid gap-3 text-sm">
                    <div><dt class="label">Source</dt><dd class="font-semibold">{{ dataset.source === 'synthetic' ? 'Synthetic, generated for demonstration' : 'Approved aggregates' }}</dd></div>
                    <div><dt class="label">Admission cycle</dt><dd class="num font-semibold">{{ dataset.cycle }}<span v-if="dataset.priorCycle" class="font-normal text-muted"> compared with {{ dataset.priorCycle }}</span></dd></div>
                    <div><dt class="label">Version</dt><dd class="num break-all font-semibold">{{ dataset.version }}</dd></div>
                    <div><dt class="label">Refreshed</dt><dd class="font-semibold">{{ refreshed }}</dd></div>
                </dl>
                <p v-else class="mt-3 text-sm text-muted">No dataset is active yet. Import one with <code>php artisan pulse:import</code>.</p>
                <p class="mt-4 rounded bg-surface-2 p-3 text-[0.8125rem] text-muted">PULSE supports decisions. Staff validate every suggestion, and nothing here claims to explain why participation differs.</p>
            </aside>
        </div>
</template>
