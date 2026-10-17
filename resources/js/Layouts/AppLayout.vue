<script setup lang="ts">
import SyntheticBanner from '@/Components/SyntheticBanner.vue';
import { getStoredTheme, setTheme, Theme } from '@/lib/theme';
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const page = usePage<PageProps>();
const dataset = computed(() => page.props.dataset);
const user = computed(() => page.props.auth.user);
const theme = ref<Theme>(getStoredTheme());

const NAV = [
    { label: 'Data trust', route: 'trust' },
    { label: 'Map', route: 'map' },
    { label: 'Pipeline', route: 'pipeline' },
    { label: 'Priority list', route: 'priority' },
    { label: 'Planner', route: 'planner' },
    { label: 'Brief', route: 'brief' },
];
</script>

<template>
    <div class="min-h-screen">
        <header class="sticky top-0 z-20 border-b border-line bg-surface">
            <div class="mx-auto flex max-w-screen-xl flex-wrap items-center gap-x-8 gap-y-2 px-4 py-3 sm:px-6">
                <Link :href="route('dashboard')" class="font-display text-lg font-semibold tracking-[0.16em]">PULSE</Link>
                <nav class="flex flex-wrap gap-x-5 gap-y-1 text-sm" aria-label="Primary">
                    <Link
                        v-for="item in NAV"
                        :key="item.route"
                        :href="route(item.route)"
                        :aria-current="route().current(item.route) ? 'page' : undefined"
                        class="border-b-2 border-transparent pb-0.5 text-muted hover:text-ink aria-[current=page]:border-accent aria-[current=page]:font-semibold aria-[current=page]:text-ink"
                    >
                        {{ item.label }}
                    </Link>
                </nav>
                <div class="ml-auto flex flex-wrap items-center gap-3 text-xs text-muted">
                    <span v-if="dataset" class="num border border-line px-2 py-0.5">{{ dataset.version }} · cycle {{ dataset.cycle }}</span>
                    <label class="flex items-center gap-1">
                        <span class="sr-only">Theme</span>
                        <select v-model="theme" class="py-0.5 pl-2 pr-7 text-xs" @change="setTheme(theme)">
                            <option value="system">System</option>
                            <option value="light">Light</option>
                            <option value="dark">Dark</option>
                        </select>
                    </label>
                    <Link :href="route('profile.edit')" class="hover:text-ink">{{ user.name }} · {{ user.role }}</Link>
                    <Link :href="route('logout')" method="post" as="button" class="hover:text-ink">Log out</Link>
                </div>
            </div>
            <SyntheticBanner />
        </header>
        <main class="mx-auto max-w-screen-xl px-4 py-6 sm:px-6">
            <header v-if="$slots.header" class="mb-5"><slot name="header" /></header>
            <slot />
        </main>
    </div>
</template>
