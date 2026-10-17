import { describe, expect, it } from 'vitest';
import { fixed, pct, regionTotals, signedPct, STATUS_LABEL } from '../lib/format';

describe('format', () => {
    it('shows an em dash for missing values instead of zero', () => {
        expect(fixed(null)).toBe('—');
        expect(pct(null)).toBe('—');
        expect(signedPct(null)).toBe('—');
    });

    it('formats numbers', () => {
        expect(fixed(12.345, 1)).toBe('12.3');
        expect(pct(0.756)).toBe('76%');
        expect(signedPct(-0.2)).toBe('-20%');
        expect(signedPct(0.034)).toBe('+3%');
        expect(signedPct(0)).toBe('0%');
    });

    it('labels every status', () => {
        expect(Object.keys(STATUS_LABEL)).toEqual(['ok', 'low', 'suppressed', 'missing']);
    });

    it('region totals include ok and low rows and never suppressed or missing', () => {
        const rows = [
            { status: 'ok', applicants: 100, examinees: 80, qualifiers: 10 },
            { status: 'low', applicants: 10, examinees: 8, qualifiers: null },
            { status: 'suppressed', applicants: null, examinees: null, qualifiers: null },
            { status: 'missing', applicants: null, examinees: null, qualifiers: null },
        ] as const;
        expect(regionTotals([...rows])).toEqual({ applicants: 110, examinees: 88, qualifiers: 10 });
    });
});
