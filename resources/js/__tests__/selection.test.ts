import { beforeEach, describe, expect, it } from 'vitest';
import { clearSelection, initialSelection, selected, select } from '../lib/selection';

beforeEach(() => {
    sessionStorage.clear();
    clearSelection();
});

describe('shared selection', () => {
    it('starts empty', () => {
        expect(selected.value).toBeNull();
    });

    it('remembers the selection for the browser session', () => {
        select('1600201000');
        expect(selected.value).toBe('1600201000');
        expect(sessionStorage.getItem('pulse-selected')).toBe('1600201000');
    });

    it('prefers a municipality named in the address over the remembered one', () => {
        select('A');
        expect(initialSelection('B', ['A', 'B'])).toBe('B');
    });

    it('falls back to the remembered one, then to the given default', () => {
        select('A');
        expect(initialSelection(null, ['A', 'B'], 'B')).toBe('A');
        clearSelection();
        expect(initialSelection(null, ['A', 'B'], 'B')).toBe('B');
    });

    it('ignores a remembered municipality that is not in this dataset', () => {
        select('GONE');
        expect(initialSelection(null, ['A', 'B'], 'A')).toBe('A');
    });

    it('still works when storage is blocked', () => {
        const original = Storage.prototype.setItem;
        Storage.prototype.setItem = () => {
            throw new Error('blocked');
        };
        expect(() => select('A')).not.toThrow();
        expect(selected.value).toBe('A');
        Storage.prototype.setItem = original;
    });
});
