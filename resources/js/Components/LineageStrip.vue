<script setup lang="ts">
import { ArrowRight } from 'lucide-vue-next';

defineProps<{ steps: { label: string; detail: string; ref: string }[] }>();
</script>

<template>
    <ol class="flex flex-col gap-2 lg:flex-row lg:items-stretch">
        <template v-for="(s, i) in steps" :key="s.label">
            <li v-if="i > 0" class="hidden items-center text-muted lg:flex" aria-hidden="true"><ArrowRight class="h-4 w-4" /></li>
            <li class="min-w-0 flex-1 rounded-md p-4" :class="i === steps.length - 1 ? 'bg-accent-soft' : 'panel'">
                <p class="label" :class="i === steps.length - 1 ? '!text-accent' : ''">{{ s.label }}</p>
                <p class="mt-0.5 text-sm font-semibold">{{ s.detail }}</p>
                <p class="num mt-0.5 break-words text-[0.8125rem] text-muted">{{ s.ref }}</p>
            </li>
        </template>
    </ol>
</template>
