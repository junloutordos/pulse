<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface Entry { id: number; psgc: string; name: string; action: string; office: string; status: string; planned_on: string; completed_on: string | null }
const props = defineProps<{ rows: MunicipalityRow[]; register: Entry[]; canPlan: boolean }>();

const { scored } = useWeights(computed(() => props.rows));
const wanted = new URLSearchParams(window.location.search).get('psgc');
const top = computed(() => scored.value.find((s) => s.rank === 1)?.psgc ?? props.rows[0].psgc);
const selected = ref(props.rows.some((r) => r.psgc === wanted) ? wanted! : top.value);
const options = computed(() => [...props.rows].sort((a, b) => a.name.localeCompare(b.name) || a.province.localeCompare(b.province)));

function complete(id: number) {
    router.patch(route('register.complete', id), {}, { preserveScroll: true });
}
</script>

<template>
    <Head title="Brief and register" />
    <AppLayout>
        <div class="grid gap-6">
            <div class="flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl">
                    <p class="label">Step 6 · Document</p>
                    <h1 class="mt-1 text-3xl font-semibold">Evidence brief and action register</h1>
                    <p class="mt-2 text-muted">One page per municipality for planning meetings. Every number carries its source and version.</p>
                </div>
                <label class="grid gap-1 text-sm" for="brief-muni">
                    <span class="label">Municipality</span>
                    <select id="brief-muni" v-model="selected" class="min-w-[16rem]">
                        <option v-for="r in options" :key="r.psgc" :value="r.psgc">{{ r.name }} ({{ r.province }})</option>
                    </select>
                </label>
            </div>

            <div class="grid gap-3">
                <div class="flex flex-wrap gap-2">
                    <a :href="route('brief.show', selected)" target="_blank" rel="noopener" class="btn">Open printable page</a>
                    <a :href="route('brief.pdf', selected)" class="btn btn-primary">Download PDF</a>
                </div>
                <iframe :key="selected" :src="route('brief.show', selected)" title="Evidence brief preview" class="h-[880px] w-full border border-line bg-white" />
            </div>

            <section aria-labelledby="register">
                <h2 id="register" class="mb-3 text-lg font-semibold">Action register</h2>
                <div class="panel overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-line text-left">
                                <th class="label px-3 py-2">Planned</th><th class="label px-3 py-2">Municipality</th><th class="label px-3 py-2">Action</th>
                                <th class="label px-3 py-2">Office</th><th class="label px-3 py-2">Status</th><th class="label px-3 py-2"><span class="sr-only">Actions</span></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in register" :key="e.id" class="border-b border-line last:border-0">
                                <td class="num px-3 py-2">{{ e.planned_on }}</td>
                                <td class="px-3 py-2 font-semibold">{{ e.name }}</td>
                                <td class="px-3 py-2">{{ e.action }}</td>
                                <td class="px-3 py-2">{{ e.office }}</td>
                                <td class="px-3 py-2"><span class="pill" :class="e.status === 'completed' ? 'pill-ok' : 'pill-plain'">{{ e.status === 'completed' ? `Completed ${e.completed_on}` : 'Planned' }}</span></td>
                                <td class="px-3 py-2 text-right">
                                    <button v-if="canPlan && e.status !== 'completed'" type="button" class="btn !px-2.5 !py-1 text-xs" @click="complete(e.id)">Mark completed</button>
                                </td>
                            </tr>
                            <tr v-if="!register.length">
                                <td colspan="6" class="px-3 py-4 text-muted">No actions yet. Accept a municipality in the Outreach planner and it appears here.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
