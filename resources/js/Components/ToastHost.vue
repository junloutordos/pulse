<script setup lang="ts">
import { dismiss, runAction, toasts } from '@/lib/toasts';
import { X } from 'lucide-vue-next';
</script>

<template>
    <div class="pointer-events-none fixed inset-x-0 bottom-4 z-50 flex flex-col items-center gap-2 px-4" aria-live="polite">
        <TransitionGroup name="toast">
            <div
                v-for="t in toasts"
                :key="t.id"
                :role="t.tone === 'error' ? 'alert' : 'status'"
                class="float pointer-events-auto flex max-w-md items-center gap-3 py-2.5 pl-4 pr-2 text-sm"
                :class="t.tone === 'error' ? 'bg-crit-bg text-crit' : ''"
            >
                <span class="font-medium">{{ t.message }}</span>
                <button v-if="t.action" type="button" class="btn btn-sm btn-quiet font-bold text-accent" @click="runAction(t.id)">{{ t.action.label }}</button>
                <button type="button" class="icon-btn !h-7 !w-7" @click="dismiss(t.id)"><X class="h-4 w-4" aria-hidden="true" /><span class="sr-only">Dismiss</span></button>
            </div>
        </TransitionGroup>
    </div>
</template>

<style>
.toast-enter-active, .toast-leave-active { transition: opacity var(--dur-2) var(--ease-out), transform var(--dur-2) var(--ease-out); }
.toast-enter-from, .toast-leave-to { opacity: 0; transform: translateY(12px) scale(0.98); }
.toast-move { transition: transform var(--dur-2) var(--ease-out); }
</style>
