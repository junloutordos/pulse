<script setup lang="ts" generic="T extends string">
defineProps<{ options: { key: T; label: string }[]; label: string; fill?: boolean }>();
const model = defineModel<T>({ required: true });
</script>

<template>
    <div role="group" :aria-label="label" class="gap-0.5 rounded-md bg-surface-2 p-0.5" :class="fill ? 'flex' : 'inline-flex flex-wrap'">
        <button
            v-for="o in options"
            :key="o.key"
            type="button"
            :aria-pressed="model === o.key"
            class="rounded py-1.5 font-medium text-muted transition-colors hover:text-ink aria-pressed:bg-surface aria-pressed:font-semibold aria-pressed:text-ink aria-pressed:shadow-sm"
            :class="fill ? 'flex-1 px-1 text-[0.8125rem]' : 'px-3 text-sm'"
            @click="model = o.key"
        >
            {{ o.label }}
        </button>
    </div>
</template>
