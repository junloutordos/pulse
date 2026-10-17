<script setup lang="ts">
import AppLayout from './AppLayout.vue';
</script>

<template>
    <AppLayout>
        <template v-if="$slots.header" #header><slot name="header" /></template>
        <slot />
    </AppLayout>
</template>
