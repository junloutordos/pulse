import { describe, expect, it } from 'vitest';
import { shortlist } from '../lib/shortlist';
import { ScoredRow } from '../lib/scoring';

const row = (psgc: string, score: number | null, rank: number | null): ScoredRow => ({
    psgc, score, rank, c_reach: null, c_attendance: null, c_decline: null,
    not_ranked_reason: score === null ? 'fewer than 15 applicants' : null,
});
const scored = [row('C', 40, 3), row('A', 90, 1), row('X', null, null), row('B', 70, 2)];

describe('shortlist', () => {
    it('returns the top slots by rank and never includes unranked municipalities', () => {
        const { picks, capped } = shortlist(scored, 2);
        expect(picks.map((p) => p.psgc)).toEqual(['A', 'B']);
        expect(capped).toBe(false);
    });

    it('slots larger than rankable count caps and flags', () => {
        const { picks, capped } = shortlist(scored, 10);
        expect(picks.map((p) => p.psgc)).toEqual(['A', 'B', 'C']);
        expect(capped).toBe(true);
    });

    it('breaks rank ties by psgc so the list is stable', () => {
        const tied = [row('B', 50, 1), row('A', 50, 1), row('C', 10, 3)];
        expect(shortlist(tied, 2).picks.map((p) => p.psgc)).toEqual(['A', 'B']);
    });

    it('treats zero, negative and fractional slots safely', () => {
        expect(shortlist(scored, 0)).toEqual({ picks: [], capped: false });
        expect(shortlist(scored, -3)).toEqual({ picks: [], capped: false });
        expect(shortlist(scored, 2.9).picks).toHaveLength(2);
    });

    it('exactly as many slots as rankable municipalities is not capped', () => {
        expect(shortlist(scored, 3).capped).toBe(false);
    });
});
