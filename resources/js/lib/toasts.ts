import { ref } from 'vue';

export interface Toast {
    id: number;
    message: string;
    tone: 'info' | 'error';
    action?: { label: string; run: () => void };
}

const MAX = 3;
const SHORT = 6000;
const WITH_ACTION = 9000; // enough time to read it and decide to undo

export const toasts = ref<Toast[]>([]);
let next = 1;
const timers = new Map<number, ReturnType<typeof setTimeout>>();

export function dismiss(id: number): void {
    clearTimeout(timers.get(id));
    timers.delete(id);
    toasts.value = toasts.value.filter((t) => t.id !== id);
}

export function notify(t: { message: string; tone?: Toast['tone']; action?: Toast['action'] }): number {
    const id = next++;
    toasts.value = [...toasts.value, { id, message: t.message, tone: t.tone ?? 'info', action: t.action }].slice(-MAX);
    timers.set(id, setTimeout(() => dismiss(id), t.action ? WITH_ACTION : SHORT));
    return id;
}

/** Runs a toast's action once; a second press does nothing. */
export function runAction(id: number): void {
    const toast = toasts.value.find((x) => x.id === id);
    if (!toast?.action) return;
    dismiss(id);
    toast.action.run();
}
