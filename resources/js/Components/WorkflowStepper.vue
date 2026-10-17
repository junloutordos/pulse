<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const STEPS = [
    { label: 'Data trust', route: 'trust' },
    { label: 'Map', route: 'map' },
    { label: 'Pipeline', route: 'pipeline' },
    { label: 'Priority list', route: 'priority' },
    { label: 'Planner', route: 'planner' },
    { label: 'Brief', route: 'brief' },
];

const page = usePage();
const list = ref<HTMLOListElement>();
const indicator = ref<{ left: number; width: number } | null>(null);
let observer: ResizeObserver | null = null;

/** The pill is one element that slides to the active step, so moving between steps reads as one movement. */
function place() {
    const active = list.value?.querySelector<HTMLElement>('[aria-current="page"]');
    indicator.value = active ? { left: active.offsetLeft, width: active.offsetWidth } : null;
}

onMounted(() => {
    place();
    observer = new ResizeObserver(place);
    if (list.value) observer.observe(list.value);
});
onBeforeUnmount(() => observer?.disconnect());
watch(() => page.url, () => nextTick(place));
</script>

<template>
    <nav aria-label="Primary" class="overflow-x-auto">
        <ol ref="list" class="relative mx-auto flex min-w-max max-w-screen-xl items-center gap-1 px-4 py-2.5 sm:px-6">
            <li
                v-if="indicator"
                aria-hidden="true"
                class="pointer-events-none absolute top-2.5 h-8 rounded-full bg-surface shadow-sm transition-[left,width] duration-[var(--dur-3)] ease-[var(--ease-out)]"
                :style="{ left: `${indicator.left}px`, width: `${indicator.width}px` }"
            />
            <template v-for="(s, i) in STEPS" :key="s.route">
                <li v-if="i > 0" aria-hidden="true" class="h-px w-5 bg-line" />
                <li class="relative">
                    <Link
                        :href="route(s.route)"
                        :aria-current="route().current(s.route) ? 'page' : undefined"
                        class="group flex items-center gap-2 rounded-full py-1 pl-1 pr-3 text-sm text-muted transition-colors hover:text-ink aria-[current=page]:font-semibold aria-[current=page]:text-ink"
                    >
                        <span
                            class="num flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold ring-1 ring-inset ring-current transition-colors group-aria-[current=page]:bg-accent group-aria-[current=page]:text-accent-ink group-aria-[current=page]:ring-0"
                        >{{ i + 1 }}</span>
                        {{ s.label }}
                    </Link>
                </li>
            </template>
        </ol>
    </nav>
</template>
