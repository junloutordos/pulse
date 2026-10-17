<script setup lang="ts">
import BrandMark from '@/Components/BrandMark.vue';
import SyntheticBanner from '@/Components/SyntheticBanner.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import UserMenu from '@/Components/UserMenu.vue';
import WorkflowStepper from '@/Components/WorkflowStepper.vue';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps<{ bleed?: boolean }>();
const dataset = computed(() => usePage<PageProps>().props.dataset);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <header class="sticky top-0 z-30 bg-shell text-shell-ink">
            <div class="mx-auto flex max-w-screen-xl items-center gap-4 px-4 py-2.5 sm:px-6">
                <Link :href="route('dashboard')" class="flex items-center gap-2.5 text-shell-ink">
                    <BrandMark />
                    <span class="font-display text-xl font-bold">PULSE</span>
                </Link>
                <div class="ml-auto flex items-center gap-1.5">
                    <span
                        v-if="dataset"
                        class="mr-1 hidden items-center gap-2 rounded-full bg-white/10 px-3 py-1 text-xs sm:inline-flex"
                        :title="`Dataset ${dataset.version}`"
                    >
                        <i class="h-2 w-2 rounded-full" :class="dataset.source === 'synthetic' ? 'bg-[#e0a64b]' : 'bg-[#6cc59a]'" aria-hidden="true" />
                        {{ dataset.source === 'synthetic' ? 'Synthetic data' : 'Real data' }}
                        <span class="text-shell-ink/60">{{ dataset.cycle }} cycle</span>
                    </span>
                    <ThemeToggle />
                    <UserMenu />
                </div>
            </div>
            <SyntheticBanner />
        </header>
        <div class="border-b border-line bg-bg"><WorkflowStepper /></div>
        <main :class="bleed ? 'flex-1' : 'mx-auto w-full max-w-screen-xl flex-1 px-4 py-8 sm:px-6'">
            <header v-if="$slots.header && !bleed" class="mb-6"><slot name="header" /></header>
            <slot />
        </main>
    </div>
</template>
