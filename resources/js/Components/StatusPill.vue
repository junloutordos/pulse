<script setup lang="ts">
import { STATUS_LABEL, StatusKey } from '@/lib/format';
import { computed } from 'vue';

const props = defineProps<{ status: StatusKey }>();
const tone = computed(() => ({ ok: 'pill-ok', low: 'pill-warn', suppressed: 'pill-crit', missing: 'pill-crit' })[props.status]);
</script>

<template>
    <span class="pill" :class="tone">{{ STATUS_LABEL[status] }}</span>
</template>
