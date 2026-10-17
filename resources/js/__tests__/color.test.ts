import { describe, expect, it } from 'vitest';
import { mixHex, parseHex } from '../lib/color';

describe('color', () => {
    it('parses six digit hex with or without a hash', () => {
        expect(parseHex('#0b4968')).toEqual([11, 73, 104]);
        expect(parseHex('FFFFFF')).toEqual([255, 255, 255]);
        expect(parseHex('rgb(1,2,3)')).toBeNull();
    });

    it('blends linearly and clamps t', () => {
        expect(mixHex('#000000', '#ffffff', 0.5)).toBe('#808080');
        expect(mixHex('#000000', '#ffffff', 0)).toBe('#000000');
        expect(mixHex('#000000', '#ffffff', 2)).toBe('#ffffff');
        expect(mixHex('#102030', '#102030', 0.3)).toBe('#102030');
    });

    it('falls back to the target for colours it cannot parse', () => {
        expect(mixHex('transparent', '#112233', 0.5)).toBe('#112233');
    });
});
