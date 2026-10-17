import { describe, expect, it } from 'vitest';
import { PRESETS, rankBand } from '../lib/rankBand';
import { IndicatorRow } from '../lib/scoring';

const rows = (f: (i: number) => Partial<IndicatorRow>): IndicatorRow[] =>
    Array.from({ length: 12 }, (_, i) => ({
        psgc: `M${String(i).padStart(2, '0')}`, status: 'ok' as const,
        rate_per_1000: i * 10 + 5, attendance: 1 - i * 0.05, change: null, ...f(i),
    }));

const reachOnly = { reach: 1, attendance: 0, decline: 0 };
const attendanceOnly = { reach: 0, attendance: 1, decline: 0 };

describe('rankBand', () => {
    it('returns lo and hi across presets', () => {
        const band = rankBand(rows(() => ({})), { reachOnly, attendanceOnly }, 'M00');
        expect(band).toEqual({ lo: 1, hi: 12, stable: false });
    });

    it('is stable when the municipality ranks near the top under every preset', () => {
        const consistent = rows((i) => ({ attendance: 0.5 + i * 0.03 }));
        expect(rankBand(consistent, { reachOnly, attendanceOnly }, 'M00')).toEqual({ lo: 1, hi: 1, stable: true });
    });

    it('is not stable when the worst rank falls outside the top 10 even if the band is narrow', () => {
        const consistent = rows((i) => ({ attendance: 0.5 + i * 0.03 }));
        expect(rankBand(consistent, { reachOnly, attendanceOnly }, 'M11')).toEqual({ lo: 12, hi: 12, stable: false });
    });

    it('returns null for a municipality that is not ranked', () => {
        const withLow = rows((i) => (i === 3 ? { status: 'low' as const } : {}));
        expect(rankBand(withLow, { reachOnly, attendanceOnly }, 'M03')).toBeNull();
    });

    it('skips presets that cannot be evaluated and returns null when none can', () => {
        const noDenominator = rows(() => ({ rate_per_1000: null }));
        expect(rankBand(noDenominator, { reachOnly }, 'M00')).toBeNull();
        expect(rankBand(noDenominator, { reachOnly, attendanceOnly }, 'M00')).toEqual({ lo: 12, hi: 12, stable: false });
    });

    it('ships the three agreed presets', () => {
        expect(Object.keys(PRESETS)).toEqual(['Balanced', 'Reach first', 'Attendance first']);
        expect(PRESETS['Reach first']).toEqual({ reach: 0.6, attendance: 0.2, decline: 0.2 });
        expect(PRESETS['Attendance first']).toEqual({ reach: 0.25, attendance: 0.55, decline: 0.2 });
    });
});
