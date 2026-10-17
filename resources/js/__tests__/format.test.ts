import { describe, expect, it } from 'vitest';
import { fixed, pct, regionShare, signedPct, STATUS_LABEL } from '../lib/format';

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

    it('region share uses only rows where both numerator and applicants are known', () => {
        const rows = [
            { status: 'ok', applicants: 100, examinees: 80 },
            { status: 'ok', applicants: 50, examinees: null },
            { status: 'low', applicants: 10, examinees: 8 },
            { status: 'suppressed', applicants: null, examinees: null },
            { status: 'missing', applicants: null, examinees: null },
        ] as const;
        expect(regionShare([...rows], 'examinees')).toBeCloseTo(88 / 110, 12);
    });

    it('region share is null when nothing can be computed', () => {
        expect(regionShare([{ status: 'suppressed', applicants: null, examinees: null }], 'examinees')).toBeNull();
        expect(regionShare([], 'examinees')).toBeNull();
    });
});
