import { ref } from 'vue';

const KEY = 'pulse-selected';

function read(): string | null {
    try {
        return sessionStorage.getItem(KEY);
    } catch {
        return null;
    }
}

/** The municipality the person is looking at, shared by every screen so it follows them through the workflow. */
export const selected = ref<string | null>(read());

export function select(psgc: string): void {
    selected.value = psgc;
    try {
        sessionStorage.setItem(KEY, psgc);
    } catch {
        // The selection then lasts for this page view only.
    }
}

export function clearSelection(): void {
    selected.value = null;
    try {
        sessionStorage.removeItem(KEY);
    } catch {
        // Nothing to clear.
    }
}

/** Address first, then what was remembered, then a default; remembered values that this dataset does not contain are ignored. */
export function initialSelection(fromAddress: string | null, available: string[], fallback?: string): string {
    const has = new Set(available);
    if (fromAddress && has.has(fromAddress)) return fromAddress;
    if (selected.value && has.has(selected.value)) return selected.value;
    return fallback ?? available[0];
}
