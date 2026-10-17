<script setup lang="ts">
defineProps<{ value?: string }>();
</script>

<template>
    <label class="block text-sm font-semibold text-ink">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
