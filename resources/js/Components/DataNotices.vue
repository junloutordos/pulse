<script setup lang="ts">
import { datasetNotices } from '@/lib/notices';
import { IndicatorRow } from '@/lib/scoring';
import { computed } from 'vue';

const props = defineProps<{ rows: IndicatorRow[] }>();
const notices = computed(() => datasetNotices(props.rows));
</script>

<template>
    <div v-if="notices.length" role="note" class="grid gap-1 border-l-2 border-warn bg-warn-bg px-3 py-2 text-sm text-warn">
        <p v-for="n in notices" :key="n">{{ n }}</p>
    </div>
</template>
