<script setup lang="ts">
defineProps<{ steps: { label: string; detail: string; ref: string }[] }>();
</script>

<template>
    <ol class="flex flex-wrap">
        <li
            v-for="(s, i) in steps"
            :key="s.label"
            class="-mb-px -ml-px min-w-[9.5rem] flex-1 border bg-surface px-3 py-2.5"
            :class="i === steps.length - 1 ? 'relative z-10 border-accent' : 'border-line'"
        >
            <span class="label">{{ s.label }}</span>
            <span class="mt-0.5 block text-sm font-semibold">{{ s.detail }}</span>
            <span class="num mt-0.5 block break-words text-xs text-muted">{{ s.ref }}</span>
        </li>
    </ol>
</template>
