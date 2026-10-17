import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { applyTheme, getStoredTheme, setTheme, THEME_KEY } from '../lib/theme';

const root = () => document.documentElement;

beforeEach(() => {
    localStorage.clear();
    root().removeAttribute('data-theme');
});
afterEach(() => vi.restoreAllMocks());

describe('theme', () => {
    it('sets data-theme for light and dark and removes it for system', () => {
        applyTheme('dark');
        expect(root().getAttribute('data-theme')).toBe('dark');
        applyTheme('light');
        expect(root().getAttribute('data-theme')).toBe('light');
        applyTheme('system');
        expect(root().hasAttribute('data-theme')).toBe(false);
    });

    it('remembers the choice', () => {
        setTheme('dark');
        expect(localStorage.getItem(THEME_KEY)).toBe('dark');
        expect(getStoredTheme()).toBe('dark');
    });

    it('treats unknown stored values as system', () => {
        localStorage.setItem(THEME_KEY, 'sepia');
        expect(getStoredTheme()).toBe('system');
    });

    it('still works when storage is blocked', () => {
        vi.spyOn(Storage.prototype, 'getItem').mockImplementation(() => { throw new Error('blocked'); });
        vi.spyOn(Storage.prototype, 'setItem').mockImplementation(() => { throw new Error('blocked'); });
        expect(getStoredTheme()).toBe('system');
        expect(() => setTheme('dark')).not.toThrow();
        expect(root().getAttribute('data-theme')).toBe('dark');
    });
});
