import { describe, expect, it } from 'vitest';
import { easeOutCubic, lerp } from '../lib/countUp';

describe('countUp helpers', () => {
    it('eases from 0 to 1 and starts fast', () => {
        expect(easeOutCubic(0)).toBe(0);
        expect(easeOutCubic(1)).toBe(1);
        expect(easeOutCubic(0.5)).toBeGreaterThan(0.5);
        expect(easeOutCubic(-1)).toBe(0);
        expect(easeOutCubic(2)).toBe(1);
    });

    it('interpolates linearly', () => {
        expect(lerp(10, 20, 0)).toBe(10);
        expect(lerp(10, 20, 1)).toBe(20);
        expect(lerp(10, 20, 0.25)).toBe(12.5);
    });
});
