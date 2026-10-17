<script setup lang="ts" generic="T extends string">
defineProps<{ options: { key: T; label: string }[]; label: string }>();
const model = defineModel<T>({ required: true });
</script>

<template>
    <div role="group" :aria-label="label" class="inline-flex flex-wrap border border-line bg-surface">
        <button
            v-for="o in options"
            :key="o.key"
            type="button"
            :aria-pressed="model === o.key"
            class="border-r border-line px-3 py-1.5 text-sm text-muted last:border-r-0 hover:text-ink aria-pressed:bg-accent aria-pressed:text-accent-ink"
            @click="model = o.key"
        >
            {{ o.label }}
        </button>
    </div>
</template>
