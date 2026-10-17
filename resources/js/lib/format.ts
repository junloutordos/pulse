export const STATUS_LABEL = {
    ok: 'Complete',
    low: 'Low sample',
    suppressed: 'Suppressed',
    missing: 'Missing',
} as const;

export type StatusKey = keyof typeof STATUS_LABEL;

const DASH = '—';

export const fixed = (v: number | null | undefined, digits = 1): string => (v == null ? DASH : v.toFixed(digits));

export const pct = (v: number | null | undefined, digits = 0): string => (v == null ? DASH : `${(v * 100).toFixed(digits)}%`);

export const signedPct = (v: number | null | undefined): string => {
    if (v == null) return DASH;
    const rounded = Math.round(v * 100);
    return `${rounded > 0 ? '+' : ''}${rounded}%`;
};

export const count = (v: number | null | undefined): string => (v == null ? DASH : v.toLocaleString('en-US'));

interface Shareable {
    status: string;
    applicants: number | null;
    examinees?: number | null;
    qualifiers?: number | null;
}

/**
 * Region-wide share of applicants for a stage. Only rows where both the stage count and the applicant count are known
 * contribute, so a withheld value is never added as zero.
 */
export function regionShare(rows: Shareable[], key: 'examinees' | 'qualifiers'): number | null {
    let numerator = 0;
    let applicants = 0;
    for (const r of rows) {
        if (r.status !== 'ok' && r.status !== 'low') continue;
        const value = r[key];
        if (value == null || r.applicants == null) continue;
        numerator += value;
        applicants += r.applicants;
    }
    return applicants > 0 ? numerator / applicants : null;
}
