<script setup lang="ts">
import PageHeader from '@/Components/PageHeader.vue';
import SelectField from '@/Components/SelectField.vue';
import { useWeights } from '@/lib/useWeights';
import { MunicipalityRow } from '@/types/rows';
import { Head, router } from '@inertiajs/vue3';
import { Download, ExternalLink } from 'lucide-vue-next';
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
        <div class="grid gap-10">
            <PageHeader>
                <template #title>Evidence brief and action register</template>
                One page per municipality for planning meetings. Every number carries its source and version.
                <template #actions>
                    <SelectField id="brief-muni" v-model="selected" label="Municipality">
                        <option v-for="r in options" :key="r.psgc" :value="r.psgc">{{ r.name }} ({{ r.province }})</option>
                    </SelectField>
                </template>
            </PageHeader>

            <section class="grid gap-3" aria-label="Brief preview">
                <div class="flex flex-wrap gap-2">
                    <a :href="route('brief.show', selected)" target="_blank" rel="noopener" class="btn">Open printable page <ExternalLink class="h-4 w-4" aria-hidden="true" /></a>
                    <a :href="route('brief.pdf', selected)" class="btn btn-primary">Download PDF <Download class="h-4 w-4" aria-hidden="true" /></a>
                </div>
                <div class="rounded-lg bg-surface-2 p-3 sm:p-8">
                    <iframe :key="selected" :src="route('brief.show', selected)" title="Evidence brief preview" class="mx-auto h-[880px] w-full max-w-[820px] bg-white" style="box-shadow: var(--shadow-float)" />
                </div>
            </section>

            <section aria-labelledby="register">
                <h2 id="register" class="mb-3 text-xl font-bold">Action register</h2>
                <div class="panel overflow-x-auto">
                    <table class="data-table">
                        <thead>
                            <tr><th>Planned</th><th>Municipality</th><th>Action</th><th>Office</th><th>Status</th><th><span class="sr-only">Actions</span></th></tr>
                        </thead>
                        <tbody>
                            <tr v-for="e in register" :key="e.id">
                                <td class="num">{{ e.planned_on }}</td>
                                <td class="font-semibold">{{ e.name }}</td>
                                <td>{{ e.action }}</td>
                                <td>{{ e.office }}</td>
                                <td><span class="pill" :class="e.status === 'completed' ? 'pill-ok' : 'pill-plain'">{{ e.status === 'completed' ? `Completed ${e.completed_on}` : 'Planned' }}</span></td>
                                <td class="text-right"><button v-if="canPlan && e.status !== 'completed'" type="button" class="btn btn-sm" @click="complete(e.id)">Mark completed</button></td>
                            </tr>
                            <tr v-if="!register.length"><td colspan="6" class="py-6 text-muted">No actions yet. Accept a municipality in the Outreach planner and it appears here.</td></tr>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
</template>
