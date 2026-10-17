<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ from: number | null; to: number | null; label: string }>();
const W = 56, H = 22;
const geometry = computed(() => {
    if (props.from === null || props.to === null) return null;
    const top = Math.max(props.from, props.to, 1) * 1.15;
    const y = (v: number) => H - 3 - (v / top) * (H - 6);
    return { y1: y(props.from), y2: y(props.to), up: props.to >= props.from };
});
const description = computed(() => (props.from === null || props.to === null ? `${props.label}: not available` : `${props.label}: from ${props.from} to ${props.to}`));
</script>

<template>
    <svg :viewBox="`0 0 ${W} ${H}`" class="h-[22px] w-14 flex-none" role="img" :aria-label="description">
        <template v-if="geometry">
            <line x1="4" :y1="geometry.y1" :x2="W - 5" :y2="geometry.y2" :class="geometry.up ? 'stroke-accent' : 'stroke-muted'" stroke-width="2" stroke-linecap="round" />
            <circle cx="4" :cy="geometry.y1" r="2" class="fill-surface" :class="geometry.up ? 'stroke-accent' : 'stroke-muted'" stroke-width="1.5" />
            <circle :cx="W - 5" :cy="geometry.y2" r="3" :class="geometry.up ? 'fill-accent' : 'fill-muted'" />
        </template>
        <line v-else x1="14" :y1="H / 2" x2="42" :y2="H / 2" class="stroke-line" stroke-width="2" stroke-linecap="round" />
    </svg>
</template>
