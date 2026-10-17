<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

const STEPS = [
    { label: 'Data trust', route: 'trust' },
    { label: 'Map', route: 'map' },
    { label: 'Pipeline', route: 'pipeline' },
    { label: 'Priority list', route: 'priority' },
    { label: 'Planner', route: 'planner' },
    { label: 'Brief', route: 'brief' },
];
</script>

<template>
    <nav aria-label="Primary" class="overflow-x-auto">
        <ol class="mx-auto flex min-w-max max-w-screen-xl items-center gap-1 px-4 py-2.5 sm:px-6">
            <template v-for="(s, i) in STEPS" :key="s.route">
                <li v-if="i > 0" aria-hidden="true" class="h-px w-5 bg-line" />
                <li>
                    <Link
                        :href="route(s.route)"
                        :aria-current="route().current(s.route) ? 'page' : undefined"
                        class="group flex items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm text-muted transition-colors hover:text-ink aria-[current=page]:bg-surface aria-[current=page]:font-semibold aria-[current=page]:text-ink aria-[current=page]:shadow-sm"
                    >
                        <span
                            class="num flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset ring-current group-aria-[current=page]:bg-accent group-aria-[current=page]:text-accent-ink group-aria-[current=page]:ring-0"
                        >{{ i + 1 }}</span>
                        {{ s.label }}
                    </Link>
                </li>
            </template>
        </ol>
    </nav>
</template>
