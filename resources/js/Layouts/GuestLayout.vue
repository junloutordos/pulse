<script setup lang="ts">
import BrandMark from '@/Components/BrandMark.vue';
import HexField from '@/Components/HexField.vue';
</script>

<template>
    <div class="grid min-h-screen lg:grid-cols-[minmax(0,1fr)_minmax(0,34rem)]">
        <div class="relative hidden overflow-hidden bg-shell text-shell-ink lg:block">
            <div class="absolute inset-0 opacity-45"><HexField /></div>
            <div class="relative z-10 flex h-full flex-col justify-between p-12">
                <div class="flex items-center gap-2.5 text-shell-ink"><BrandMark /><span class="font-display text-xl font-bold">PULSE</span></div>
                <div class="max-w-md rounded-lg bg-shell p-6">
                    <h2 class="font-display text-3xl font-bold leading-tight">Where should NCE outreach go next, and why?</h2>
                    <p class="mt-3 text-shell-ink/70">Geospatial decision support for PSHS-Caraga Region Campus. Approved municipality summaries in, an explainable shortlist out.</p>
                </div>
            </div>
        </div>
        <div class="flex flex-col justify-center px-6 py-12 sm:px-12">
            <div class="mb-8 flex items-center gap-2.5 lg:hidden"><BrandMark /><span class="font-display text-xl font-bold">PULSE</span></div>
            <div class="w-full max-w-sm">
                <slot />
            </div>
        </div>
    </div>
</template>
