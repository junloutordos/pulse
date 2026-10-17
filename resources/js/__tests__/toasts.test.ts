import { afterEach, beforeEach, describe, expect, it, vi } from 'vitest';
import { dismiss, notify, runAction, toasts } from '../lib/toasts';

beforeEach(() => {
    vi.useFakeTimers();
    toasts.value = [];
});
afterEach(() => vi.useRealTimers());

describe('toasts', () => {
    it('shows a message and removes it after its time', () => {
        notify({ message: 'Accepted Santa Monica' });
        expect(toasts.value.map((t) => t.message)).toEqual(['Accepted Santa Monica']);
        vi.advanceTimersByTime(6100);
        expect(toasts.value).toHaveLength(0);
    });

    it('keeps a toast with an action a little longer so there is time to undo', () => {
        notify({ message: 'Accepted', action: { label: 'Undo', run: () => {} } });
        vi.advanceTimersByTime(6100);
        expect(toasts.value).toHaveLength(1);
        vi.advanceTimersByTime(3000);
        expect(toasts.value).toHaveLength(0);
    });

    it('runs the action once and dismisses the toast', () => {
        const run = vi.fn();
        const id = notify({ message: 'Accepted', action: { label: 'Undo', run } });
        runAction(id);
        runAction(id);
        expect(run).toHaveBeenCalledTimes(1);
        expect(toasts.value).toHaveLength(0);
    });

    it('shows at most three at once, dropping the oldest', () => {
        for (const m of ['a', 'b', 'c', 'd']) notify({ message: m });
        expect(toasts.value.map((t) => t.message)).toEqual(['b', 'c', 'd']);
    });

    it('dismisses on request and ignores unknown ids', () => {
        const id = notify({ message: 'x' });
        dismiss(999);
        expect(toasts.value).toHaveLength(1);
        dismiss(id);
        expect(toasts.value).toHaveLength(0);
    });

    it('marks failures so they can be announced more firmly', () => {
        notify({ message: 'Could not save', tone: 'error' });
        expect(toasts.value[0].tone).toBe('error');
    });
});
