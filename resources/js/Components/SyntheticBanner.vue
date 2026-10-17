<script setup lang="ts">
import { PageProps } from '@/types';
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const dataset = computed(() => usePage<PageProps>().props.dataset);
</script>

<template>
    <div v-if="dataset?.source === 'synthetic'" role="status" class="bg-warn-bg px-4 py-1 text-center text-[0.8125rem] text-warn sm:px-6">
        <strong class="font-semibold">Synthetic data.</strong>
        Generated for demonstration. These are not real NCE figures.
    </div>
</template>
