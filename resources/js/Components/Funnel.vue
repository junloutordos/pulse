<script setup lang="ts">
import { count, pct } from '@/lib/format';

defineProps<{
    stages: { label: string; value: number | null; share: number | null; regionShare: number | null }[];
}>();
</script>

<template>
    <div class="grid gap-3">
        <div v-for="s in stages" :key="s.label">
            <div class="flex items-baseline justify-between text-sm">
                <span>{{ s.label }}</span>
                <span class="num">{{ count(s.value) }}</span>
            </div>
            <div class="relative mt-1 h-2 bg-bg" role="img" :aria-label="`${s.label}: ${pct(s.share)} of applicants, region ${pct(s.regionShare)}`">
                <div class="h-full bg-accent transition-[width] duration-300" :style="{ width: `${(s.share ?? 0) * 100}%` }" />
                <span v-if="s.regionShare !== null" class="absolute -bottom-1 -top-1 w-0.5 bg-ink" :style="{ left: `calc(${(s.regionShare ?? 0) * 100}% - 1px)` }" aria-hidden="true" />
            </div>
            <p v-if="s.regionShare !== null" class="mt-1 text-right text-xs text-muted">Region {{ pct(s.regionShare) }}</p>
        </div>
    </div>
</template>
