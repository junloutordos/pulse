<script setup lang="ts">
import { PageProps } from '@/types';
import { Link, usePage } from '@inertiajs/vue3';
import { LogOut, UserRound } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const user = computed(() => usePage<PageProps>().props.auth.user);
const initials = computed(() => user.value.name.split(/\s+/).map((p) => p[0]).slice(0, 2).join('').toUpperCase());
const open = ref(false);
const root = ref<HTMLElement>();

const close = (e: Event) => {
    if (!root.value?.contains(e.target as Node)) open.value = false;
};
const esc = (e: KeyboardEvent) => e.key === 'Escape' && (open.value = false);
onMounted(() => {
    document.addEventListener('click', close);
    document.addEventListener('keydown', esc);
});
onBeforeUnmount(() => {
    document.removeEventListener('click', close);
    document.removeEventListener('keydown', esc);
});
</script>

<template>
    <div ref="root" class="relative">
        <button
            type="button"
            class="flex h-9 w-9 items-center justify-center rounded-full bg-accent-soft text-[0.8125rem] font-bold text-accent"
            :aria-expanded="open"
            aria-haspopup="menu"
            @click="open = !open"
        >
            <span aria-hidden="true">{{ initials }}</span>
            <span class="sr-only">Account menu for {{ user.name }}</span>
        </button>
        <div v-if="open" role="menu" class="float absolute right-0 top-11 z-40 w-56 p-1.5 text-sm text-ink">
            <div class="px-3 py-2">
                <p class="font-semibold">{{ user.name }}</p>
                <p class="text-[0.8125rem] capitalize text-muted">{{ user.role }}</p>
            </div>
            <Link :href="route('profile.edit')" role="menuitem" class="flex items-center gap-2 rounded px-3 py-2 hover:bg-surface-2"><UserRound class="h-4 w-4" aria-hidden="true" />Profile</Link>
            <Link :href="route('logout')" method="post" as="button" role="menuitem" class="flex w-full items-center gap-2 rounded px-3 py-2 text-left hover:bg-surface-2"><LogOut class="h-4 w-4" aria-hidden="true" />Log out</Link>
        </div>
    </div>
</template>
