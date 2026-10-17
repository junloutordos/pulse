import { Weights } from './scoring';

export const formatWeights = (w: Weights): string => [w.reach, w.attendance, w.decline].map((v) => Math.round(v * 100)).join(',');

/** Three whole numbers from 0 to 100 that are not all zero, as written by formatWeights. */
export function parseWeights(value: string | null): Weights | null {
    if (!value) return null;
    const parts = value.split(',');
    if (parts.length !== 3 || !parts.every((p) => /^\d{1,3}$/.test(p))) return null;
    const [reach, attendance, decline] = parts.map(Number);
    if ([reach, attendance, decline].some((n) => n > 100) || reach + attendance + decline === 0) return null;
    return { reach: reach / 100, attendance: attendance / 100, decline: decline / 100 };
}

export const readParam = (name: string): string | null => new URLSearchParams(window.location.search).get(name);

/** Updates query parameters in place (null removes one) so a view can be shared or bookmarked, without growing the back stack. */
export function replaceParams(updates: Record<string, string | null>): void {
    const url = new URL(window.location.href);
    for (const [key, value] of Object.entries(updates)) {
        if (value === null) url.searchParams.delete(key);
        else url.searchParams.set(key, value);
    }
    window.history.replaceState(window.history.state, '', `${url.pathname}${url.search}${url.hash}`);
}

/** Copies the current address. Returns false when the browser refuses, so the caller can say so. */
export async function copyLink(): Promise<boolean> {
    try {
        await navigator.clipboard.writeText(window.location.href);
        return true;
    } catch {
        return false;
    }
}
