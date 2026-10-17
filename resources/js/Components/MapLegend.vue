<script setup lang="ts">
import { computed } from 'vue';

export interface LegendItem {
    label: string;
    color: string;
    count?: number;
}

const props = defineProps<{ title: string; items: LegendItem[]; noValue: number; note?: string }>();
const max = computed(() => Math.max(1, ...props.items.map((i) => i.count ?? 0)));
const hasCounts = computed(() => props.items.some((i) => i.count !== undefined));
</script>

<template>
    <div>
        <p class="text-sm font-semibold">{{ title }}</p>
        <ul class="mt-3 flex gap-1" aria-label="Legend with the number of municipalities in each class">
            <li v-for="i in items" :key="i.label" class="min-w-0 flex-1">
                <div v-if="hasCounts" class="flex h-5 items-end" aria-hidden="true">
                    <div class="w-full rounded-t-sm bg-ink/15" :style="{ height: `${Math.max(2, ((i.count ?? 0) / max) * 100)}%` }" />
                </div>
                <div class="h-2.5 w-full" :style="{ background: i.color }" />
                <p class="mt-1 truncate text-[0.6875rem] leading-tight text-muted" :title="i.label">{{ i.label }}</p>
                <p v-if="hasCounts" class="num text-[0.6875rem] font-semibold">{{ i.count }}</p>
            </li>
        </ul>
        <p class="mt-3 flex items-center gap-2 text-[0.8125rem] text-muted">
            <i class="inline-block h-3 w-5 rounded-sm border border-line" style="background: repeating-linear-gradient(135deg, transparent 0 3px, var(--muted) 3px 4px)" aria-hidden="true" />
            No value ({{ noValue }}): suppressed, missing or not computed
        </p>
        <p v-if="note" class="mt-2 text-[0.8125rem] text-muted">{{ note }}</p>
    </div>
</template>
