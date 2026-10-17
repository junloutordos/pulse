<template>
    <header class="flex flex-wrap items-end justify-between gap-x-8 gap-y-3">
        <div class="max-w-2xl">
            <h1 class="text-[2rem] font-bold leading-[1.1]"><slot name="title" /></h1>
            <p v-if="$slots.default" class="mt-2 text-[0.9375rem] text-muted"><slot /></p>
        </div>
        <div v-if="$slots.actions" class="flex flex-wrap items-center gap-3"><slot name="actions" /></div>
    </header>
</template>
