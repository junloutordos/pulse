import { describe, expect, it } from 'vitest';
import { biggestMover, rankDeltas } from '../lib/rankDeltas';

const ranks = (o: Record<string, number>) => new Map(Object.entries(o));

describe('rankDeltas', () => {
    it('is positive when a municipality moved up (to a smaller rank number)', () => {
        expect(rankDeltas(ranks({ A: 5, B: 1 }), ranks({ A: 2, B: 1 }))).toEqual({ A: 3 });
    });

    it('is negative when it moved down', () => {
        expect(rankDeltas(ranks({ A: 1, B: 2 }), ranks({ A: 2, B: 1 }))).toEqual({ A: -1, B: 1 });
    });

    it('ignores municipalities that did not move or were not ranked before or after', () => {
        expect(rankDeltas(ranks({ A: 1, B: 2 }), ranks({ A: 1, C: 2 }))).toEqual({});
    });

    it('returns nothing on the first render', () => {
        expect(rankDeltas(null, ranks({ A: 1 }))).toEqual({});
    });
});

describe('biggestMover', () => {
    it('names the municipality that moved furthest, with where it was and is', () => {
        const prev = ranks({ A: 5, B: 1, C: 3 });
        const next = ranks({ A: 2, B: 3, C: 4 });
        expect(biggestMover(prev, next)).toEqual({ psgc: 'A', from: 5, to: 2 });
    });

    it('is null when nothing moved', () => {
        expect(biggestMover(ranks({ A: 1 }), ranks({ A: 1 }))).toBeNull();
    });
});
