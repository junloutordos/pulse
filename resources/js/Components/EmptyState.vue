<script setup lang="ts">
defineProps<{ title: string }>();
</script>

<template>
    <div class="grid justify-items-center gap-2 px-6 py-12 text-center">
        <svg viewBox="0 0 64 56" class="h-12 w-14 text-line" aria-hidden="true">
            <polygon points="22,4 36,12 36,28 22,36 8,28 8,12" fill="currentColor" /><polygon points="42,20 56,28 56,44 42,52 28,44 28,28" fill="var(--accent-soft)" stroke="currentColor" stroke-width="2" />
        </svg>
        <p class="font-display text-lg font-bold">{{ title }}</p>
        <p class="max-w-sm text-sm text-muted"><slot /></p>
        <div v-if="$slots.actions" class="mt-2"><slot name="actions" /></div>
    </div>
</template>
