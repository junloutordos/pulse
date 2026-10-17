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

interface Countable {
    status: string;
    applicants: number | null;
    examinees: number | null;
    qualifiers: number | null;
}

/** Region totals use only rows that are shown (ok and low); suppressed and missing rows are never counted as zero. */
export function regionTotals(rows: Countable[]): { applicants: number; examinees: number; qualifiers: number } {
    const totals = { applicants: 0, examinees: 0, qualifiers: 0 };
    for (const r of rows) {
        if (r.status !== 'ok' && r.status !== 'low') continue;
        totals.applicants += r.applicants ?? 0;
        totals.examinees += r.examinees ?? 0;
        totals.qualifiers += r.qualifiers ?? 0;
    }
    return totals;
}
