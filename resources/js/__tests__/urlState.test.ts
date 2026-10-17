import { beforeEach, describe, expect, it } from 'vitest';
import { formatWeights, parseWeights, readParam, replaceParams } from '../lib/urlState';

beforeEach(() => window.history.replaceState(null, '', '/priority'));

describe('weights in the address', () => {
    it('round-trips', () => {
        expect(formatWeights({ reach: 0.6, attendance: 0.2, decline: 0.2 })).toBe('60,20,20');
        expect(parseWeights('60,20,20')).toEqual({ reach: 0.6, attendance: 0.2, decline: 0.2 });
    });

    it('rejects anything that is not three whole numbers from 0 to 100', () => {
        for (const bad of [null, '', '1,2', '1,2,3,4', 'a,b,c', '101,0,0', '-1,5,5', '1.5,2,3', '0,0,0']) expect(parseWeights(bad as string | null)).toBeNull();
    });

    it('rounds when formatting', () => {
        expect(formatWeights({ reach: 1 / 3, attendance: 1 / 3, decline: 1 / 3 })).toBe('33,33,33');
    });
});

describe('replaceParams', () => {
    it('adds, changes and removes query parameters without adding history entries', () => {
        const before = window.history.length;
        replaceParams({ metric: 'attendance', shape: 'tile' });
        expect(location.search).toBe('?metric=attendance&shape=tile');
        replaceParams({ shape: null, psgc: '123' });
        expect(location.search).toBe('?metric=attendance&psgc=123');
        expect(window.history.length).toBe(before);
    });

    it('keeps the path and the hash', () => {
        window.history.replaceState(null, '', '/map#top');
        replaceParams({ a: '1' });
        expect(location.pathname + location.search + location.hash).toBe('/map?a=1#top');
    });
});

describe('readParam', () => {
    it('reads from the current address', () => {
        window.history.replaceState(null, '', '/map?metric=score');
        expect(readParam('metric')).toBe('score');
        expect(readParam('nope')).toBeNull();
    });
});
