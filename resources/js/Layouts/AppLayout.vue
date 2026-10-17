<script setup lang="ts">
import BrandMark from '@/Components/BrandMark.vue';
import CommandPalette from '@/Components/CommandPalette.vue';
import SyntheticBanner from '@/Components/SyntheticBanner.vue';
import ToastHost from '@/Components/ToastHost.vue';
import ThemeToggle from '@/Components/ThemeToggle.vue';
import UserMenu from '@/Components/UserMenu.vue';
import WorkflowStepper from '@/Components/WorkflowStepper.vue';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { Search } from 'lucide-vue-next';
import { computed, nextTick, ref, watch } from 'vue';

defineProps<{ bleed?: boolean }>();
const page = usePage<PageProps>();
const dataset = computed(() => page.props.dataset);
const palette = ref(false);
const main = ref<HTMLElement>();

// After moving to another screen, put keyboard and screen-reader focus on the new content rather than leaving it on the old link.
// Only a change of screen counts: the first load and same-screen address updates leave focus alone.
watch(() => page.component, () => {
    nextTick(() => {
        if (!document.activeElement?.closest('[role=dialog]')) main.value?.focus({ preventScroll: true });
    });
});
const isMac = typeof navigator !== 'undefined' && /Mac|iPhone|iPad/.test(navigator.platform);
</script>

<template>
    <div class="flex min-h-screen flex-col">
        <a href="#main" class="sr-only z-[70] rounded-md bg-surface px-4 py-2 font-semibold text-ink focus:not-sr-only focus:fixed focus:left-4 focus:top-4 float" @click.prevent="main?.focus()">Skip to content</a>
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
                    <button type="button" class="mr-1 hidden h-9 items-center gap-2 rounded-full bg-white/10 px-3 text-xs text-shell-ink/80 transition-colors hover:bg-white/15 md:inline-flex" @click="palette = true">
                        <Search class="h-3.5 w-3.5" aria-hidden="true" />Jump to
                        <kbd class="rounded bg-white/10 px-1.5 py-0.5 font-sans text-[0.6875rem]">{{ isMac ? '⌘' : 'Ctrl' }} K</kbd>
                    </button>
                    <button type="button" class="icon-btn hover:!bg-white/10 md:hidden" @click="palette = true"><Search class="h-[18px] w-[18px]" aria-hidden="true" /><span class="sr-only">Jump to a screen or municipality</span></button>
                    <ThemeToggle />
                    <UserMenu />
                </div>
            </div>
            <SyntheticBanner />
        </header>
        <div class="border-b border-line bg-bg"><WorkflowStepper /></div>
        <main id="main" ref="main" tabindex="-1" :class="bleed ? 'flex-1 outline-none' : 'mx-auto w-full max-w-screen-xl flex-1 px-4 py-8 outline-none sm:px-6'">
            <div :key="page.component" class="page-enter">
                <header v-if="$slots.header && !bleed" class="mb-6"><slot name="header" /></header>
                <slot />
            </div>
        </main>
        <ToastHost />
        <CommandPalette v-model="palette" />
    </div>
</template>
