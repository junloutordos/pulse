<script setup lang="ts">
import { computed } from 'vue';

const emit = defineEmits(['update:checked']);
const props = defineProps<{ checked: boolean | unknown[]; value?: string }>();

const proxyChecked = computed({
    get() {
        return props.checked;
    },
    set(val) {
        emit('update:checked', val);
    },
});
</script>

<template>
    <input v-model="proxyChecked" type="checkbox" :value="value" class="h-4 w-4 rounded-sm border-0 bg-surface text-accent focus:ring-accent" style="box-shadow: 0 0 0 1px color-mix(in srgb, var(--ink) 30%, transparent)" />
</template>
