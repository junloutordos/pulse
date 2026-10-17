<script setup lang="ts">
withDefaults(defineProps<{ type?: 'button' | 'submit' | 'reset' }>(), { type: 'button' });
</script>

<template>
    <button :type="type" class="btn h-10 px-5"><slot /></button>
</template>
