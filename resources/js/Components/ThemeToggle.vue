<script setup lang="ts">
import { getStoredTheme, setTheme, Theme } from '@/lib/theme';
import { Monitor, Moon, Sun } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const theme = ref<Theme>(getStoredTheme());
const ORDER: Theme[] = ['system', 'light', 'dark'];
const LABEL = { system: 'System', light: 'Light', dark: 'Dark' } as const;
const icon = computed(() => ({ system: Monitor, light: Sun, dark: Moon })[theme.value]);

function next() {
    theme.value = ORDER[(ORDER.indexOf(theme.value) + 1) % ORDER.length];
    setTheme(theme.value);
}
</script>

<template>
    <button type="button" class="icon-btn hover:!bg-white/10" :title="`Theme: ${LABEL[theme]}. Click to change.`" @click="next">
        <component :is="icon" class="h-[18px] w-[18px]" aria-hidden="true" />
        <span class="sr-only">Theme: {{ LABEL[theme] }}. Click to change.</span>
    </button>
</template>
