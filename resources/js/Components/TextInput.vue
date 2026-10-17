<script setup lang="ts">
import { onMounted, ref } from 'vue';

const model = defineModel<string>({ required: true });
const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) input.value?.focus();
});

defineExpose({ focus: () => input.value?.focus() });
</script>

<template>
    <input
        v-model="model"
        ref="input"
        class="h-11 rounded border-0 bg-surface px-3.5 text-ink placeholder:text-muted focus:ring-2 focus:ring-accent"
        style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--ink) 18%, transparent)"
    />
</template>
