import { describe, expect, it } from 'vitest';
import { binOf, quantileBreaks } from '../lib/breaks';

describe('quantileBreaks', () => {
    it('returns bins minus one thresholds from the sorted values', () => {
        expect(quantileBreaks([1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11], 5)).toEqual([3, 5, 7, 9]);
    });

    it('ignores nulls', () => {
        const withNulls = [null, 1, 2, null, 3, 4, 5, 6, 7, 8, 9, 10, 11, null];
        expect(quantileBreaks(withNulls, 5)).toEqual([3, 5, 7, 9]);
    });

    it('returns no thresholds when there is nothing to bin', () => {
        expect(quantileBreaks([null, null], 5)).toEqual([]);
        expect(quantileBreaks([], 5)).toEqual([]);
    });

    it('repeats the value when there is a single observation', () => {
        expect(quantileBreaks([7], 5)).toEqual([7, 7, 7, 7]);
    });
});

describe('binOf', () => {
    const breaks = [3, 5, 7, 9];
    it('puts a value in the bin after the thresholds it exceeds', () => {
        expect(binOf(1, breaks)).toBe(0);
        expect(binOf(3, breaks)).toBe(0);
        expect(binOf(3.1, breaks)).toBe(1);
        expect(binOf(10, breaks)).toBe(4);
    });

    it('returns null for null so unfilled municipalities stay unfilled', () => {
        expect(binOf(null, breaks)).toBeNull();
    });
});
