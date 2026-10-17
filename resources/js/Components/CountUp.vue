<script setup lang="ts">
import { easeOutCubic, lerp } from '@/lib/countUp';
import { prefersReducedMotion } from '@/lib/motion';
import { onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(defineProps<{ value: number | null; format?: (n: number) => string; duration?: number }>(), { duration: 420 });

const shown = ref<number | null>(props.value);
let raf = 0;

function run(to: number | null, from: number | null) {
    cancelAnimationFrame(raf);
    if (to === null || from === null || prefersReducedMotion() || from === to) {
        shown.value = to;
        return;
    }
    const start = performance.now();
    const step = (now: number) => {
        const k = Math.min(1, (now - start) / props.duration);
        shown.value = lerp(from, to, easeOutCubic(k));
        if (k < 1) raf = requestAnimationFrame(step);
        else shown.value = to;
    };
    raf = requestAnimationFrame(step);
}

// Counts up once when first shown, then from the old value to the new one whenever the value changes.
onMounted(() => run(props.value, props.value === null ? null : 0));
watch(() => props.value, (to) => run(to, shown.value));
onBeforeUnmount(() => cancelAnimationFrame(raf));
</script>

<template>
    <span class="num">{{ shown === null ? '—' : (format ?? String)(shown) }}</span>
</template>
