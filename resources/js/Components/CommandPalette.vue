<script setup lang="ts">
import { buildItems, PaletteItem, searchItems } from '@/lib/palette';
import { select } from '@/lib/selection';
import { PageProps } from '@/types';
import { router, usePage } from '@inertiajs/vue3';
import { CornerDownLeft, MapPin, Search } from 'lucide-vue-next';
import { computed, nextTick, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const open = defineModel<boolean>({ required: true });
const page = usePage<PageProps>();
const items = computed(() => buildItems(page.props.municipalities ?? []));
const query = ref('');
const active = ref(0);
const input = ref<HTMLInputElement>();
const results = computed(() => searchItems(query.value, items.value));
let returnTo: HTMLElement | null = null;

watch(open, async (v) => {
    if (v) {
        returnTo = document.activeElement as HTMLElement | null;
        query.value = '';
        active.value = 0;
        await nextTick();
        input.value?.focus();
    } else {
        returnTo?.focus();
    }
});
watch(query, () => (active.value = 0));

// Screens that show one selected municipality stay where they are; everywhere else the map is the natural place to look.
const SELECTABLE = ['map', 'pipeline', 'brief'];
function go(item: PaletteItem) {
    open.value = false;
    if (item.kind === 'screen') return void router.visit(route(item.route!));
    select(item.psgc!);
    const here = SELECTABLE.find((r) => route().current(r));
    router.visit(route(here ?? 'map', { psgc: item.psgc }));
}

function onKey(e: KeyboardEvent) {
    if ((e.metaKey || e.ctrlKey) && e.key.toLowerCase() === 'k') {
        e.preventDefault();
        open.value = !open.value;
    }
}
function onPaletteKey(e: KeyboardEvent) {
    if (e.key === 'Escape') open.value = false;
    else if (e.key === 'ArrowDown') (e.preventDefault(), (active.value = Math.min(results.value.length - 1, active.value + 1)));
    else if (e.key === 'ArrowUp') (e.preventDefault(), (active.value = Math.max(0, active.value - 1)));
    else if (e.key === 'Enter' && results.value[active.value]) (e.preventDefault(), go(results.value[active.value]));
}
onMounted(() => document.addEventListener('keydown', onKey));
onBeforeUnmount(() => document.removeEventListener('keydown', onKey));
</script>

<template>
    <Teleport to="body">
        <Transition name="palette">
            <div v-if="open" class="fixed inset-0 z-[60] flex items-start justify-center bg-ink/40 px-4 pt-[14vh]" @mousedown.self="open = false">
                <div class="float w-full max-w-xl overflow-hidden" role="dialog" aria-modal="true" aria-label="Jump to a screen or municipality" @keydown="onPaletteKey">
                    <div class="flex items-center gap-3 border-b border-line px-4">
                        <Search class="h-4 w-4 flex-none text-muted" aria-hidden="true" />
                        <input
                            ref="input"
                            v-model="query"
                            type="text"
                            role="combobox"
                            aria-expanded="true"
                            aria-controls="palette-results"
                            :aria-activedescendant="results[active] ? `palette-${results[active].id}` : undefined"
                            placeholder="Jump to a screen or municipality"
                            class="h-12 w-full border-0 bg-transparent px-0 text-base focus:ring-0"
                            style="box-shadow: none"
                        />
                        <kbd class="hidden rounded bg-surface-2 px-1.5 py-0.5 text-xs text-muted sm:block">Esc</kbd>
                    </div>
                    <ul id="palette-results" role="listbox" class="max-h-[22rem] overflow-y-auto p-1.5">
                        <li v-for="(r, i) in results" :id="`palette-${r.id}`" :key="r.id" role="option" :aria-selected="i === active">
                            <button type="button" class="flex w-full items-center gap-3 rounded px-3 py-2.5 text-left text-sm" :class="i === active ? 'bg-accent-soft' : 'hover:bg-surface-2'" @mousemove="active = i" @click="go(r)">
                                <MapPin v-if="r.kind === 'municipality'" class="h-4 w-4 flex-none text-muted" aria-hidden="true" />
                                <span v-else class="h-4 w-4 flex-none" aria-hidden="true" />
                                <span class="font-semibold">{{ r.label }}</span>
                                <span class="ml-auto text-[0.8125rem] text-muted">{{ r.hint }}</span>
                                <CornerDownLeft v-if="i === active" class="h-3.5 w-3.5 flex-none text-accent" aria-hidden="true" />
                            </button>
                        </li>
                        <li v-if="!results.length" class="px-4 py-8 text-center text-sm text-muted">Nothing matches “{{ query }}”. Try a municipality, a province or a screen name.</li>
                    </ul>
                </div>
            </div>
        </Transition>
    </Teleport>
</template>

<style>
.palette-enter-active, .palette-leave-active { transition: opacity var(--dur-2) var(--ease-out); }
.palette-enter-active .float, .palette-leave-active .float { transition: transform var(--dur-2) var(--ease-out); }
.palette-enter-from, .palette-leave-to { opacity: 0; }
.palette-enter-from .float, .palette-leave-to .float { transform: translateY(-8px) scale(0.98); }
</style>
