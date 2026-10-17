import { describe, expect, it } from 'vitest';
import cases from '../../../shared/fixtures/scoring_cases.json';
import { AllZeroWeightsError, IndicatorRow, priorityScores, Weights } from '../lib/scoring';

type Case = {
    name: string;
    weights: Weights;
    rows: IndicatorRow[];
    expected: { psgc: string; score: number | null; rank: number | null; not_ranked_reason: string | null }[];
};
const CASES = cases as unknown as Case[];
const byName = (name: string) => CASES.find((c) => c.name === name)!;

describe('priorityScores', () => {
    it('explains missing components in plain language', () => {
        const rows: IndicatorRow[] = [
            { psgc: 'A', status: 'ok', rate_per_1000: 10, attendance: 0.9, change: 0.1 },
            { psgc: 'B', status: 'ok', rate_per_1000: 20, attendance: 0.8, change: 0 },
            { psgc: 'C', status: 'ok', rate_per_1000: null, attendance: 0.7, change: 0.2 },
            { psgc: 'D', status: 'ok', rate_per_1000: 30, attendance: null, change: 0.2 },
            { psgc: 'E', status: 'ok', rate_per_1000: 40, attendance: 0.6, change: null },
        ];
        const out = Object.fromEntries(priorityScores(rows, { reach: 1, attendance: 1, decline: 1 }).map((r) => [r.psgc, r.not_ranked_reason]));
        expect(out.C).toBe('no Grade 6 population (rate not computed)');
        expect(out.D).toBe('no exam attendance');
        expect(out.E).toBe('no prior-cycle comparison');
    });

    for (const c of CASES) {
        it(`matches shared fixture: ${c.name}`, () => {
            const out = priorityScores(c.rows, c.weights);
            for (const exp of c.expected) {
                const got = out.find((r) => r.psgc === exp.psgc)!;
                if (exp.score === null) {
                    expect(got.score).toBeNull();
                    expect(got.rank).toBeNull();
                } else {
                    expect(got.score).toBeCloseTo(exp.score, 9);
                    expect(got.rank).toBe(exp.rank);
                }
                expect(got.not_ranked_reason).toBe(exp.not_ranked_reason);
            }
        });
    }

    it('drops decline when no prior cycle', () => {
        const c = byName('single cycle drops decline');
        const out = priorityScores(c.rows, c.weights);
        expect(out.every((r) => r.c_decline === null)).toBe(true);
        expect(out[0].c_reach).not.toBeNull();
    });

    it('drops reach when no denominator', () => {
        const c = byName('no denominator drops reach');
        const out = priorityScores(c.rows, c.weights);
        expect(out.every((r) => r.c_reach === null)).toBe(true);
        expect(out[0].c_attendance).not.toBeNull();
    });

    it('all zero weights throws AllZeroWeightsError', () => {
        const rows = CASES[0].rows;
        expect(() => priorityScores(rows, { reach: 0, attendance: 0, decline: 0 })).toThrow(AllZeroWeightsError);
    });

    it('zero weight on the only available components throws AllZeroWeightsError', () => {
        const c = byName('no denominator drops reach');
        expect(() => priorityScores(c.rows, { reach: 1, attendance: 0, decline: 0 })).toThrow(AllZeroWeightsError);
    });

    it('negative weights throw RangeError', () => {
        expect(() => priorityScores(CASES[0].rows, { reach: 1, attendance: -1, decline: 1 })).toThrow(RangeError);
    });

    it('a single rankable municipality scores its components as 1', () => {
        const rows: IndicatorRow[] = [{ psgc: 'A', status: 'ok', rate_per_1000: 5, attendance: 0.5, change: -0.1 }];
        const [row] = priorityScores(rows, { reach: 1, attendance: 1, decline: 1 });
        expect(row.score).toBeCloseTo(100, 9);
        expect(row.rank).toBe(1);
    });
});
