<script setup lang="ts">
import { ChevronDown } from 'lucide-vue-next';

defineProps<{ id: string; label: string; hideLabel?: boolean }>();
const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="grid gap-1">
        <label :for="id" class="label" :class="{ 'sr-only': hideLabel }">{{ label }}</label>
        <div class="relative">
            <select
                :id="id"
                v-model="model"
                class="h-9 w-full min-w-[12rem] appearance-none rounded border-0 bg-surface py-0 pl-3 pr-9 text-sm font-medium text-ink"
                style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--ink) 14%, transparent)"
            >
                <slot />
            </select>
            <ChevronDown class="pointer-events-none absolute right-2.5 top-1/2 h-4 w-4 -translate-y-1/2 text-muted" aria-hidden="true" />
        </div>
    </div>
</template>
