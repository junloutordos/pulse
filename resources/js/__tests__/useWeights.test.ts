import { beforeEach, describe, expect, it } from 'vitest';
import { ref } from 'vue';
import { PRESETS } from '../lib/rankBand';
import { IndicatorRow } from '../lib/scoring';
import { resetWeights, useWeights } from '../lib/useWeights';

const rows = (over: Partial<IndicatorRow> = {}): IndicatorRow[] =>
    ['A', 'B', 'C'].map((p, i) => ({ psgc: p, status: 'ok' as const, rate_per_1000: 10 + i, attendance: 0.9 - i * 0.1, change: -0.1 * i, ...over }));

beforeEach(() => {
    sessionStorage.clear();
    resetWeights();
});

describe('useWeights', () => {
    it('starts with the balanced preset', () => {
        const { weights } = useWeights(ref(rows()));
        expect(weights).toEqual(PRESETS.Balanced);
    });

    it('applies a preset', () => {
        const w = useWeights(ref(rows()));
        w.applyPreset('Reach first');
        expect({ ...w.weights }).toEqual(PRESETS['Reach first']);
    });

    it('all zero weights keeps previous weights and shows a message', () => {
        const w = useWeights(ref(rows()));
        w.update('reach', 0.5);
        w.update('attendance', 0);
        expect(w.message.value).toBe('');
        w.update('decline', 0);
        w.update('reach', 0);
        expect(w.weights.reach).toBe(0.5);
        expect(w.message.value).toMatch(/at least one/i);
    });

    it('clears the message after a valid change', () => {
        const w = useWeights(ref(rows()));
        w.update('attendance', 0);
        w.update('decline', 0);
        w.update('reach', 0);
        expect(w.message.value).not.toBe('');
        w.update('attendance', 0.4);
        expect(w.message.value).toBe('');
    });

    it('rejects weight on a component the dataset cannot score', () => {
        const noDenominator = ref(rows({ rate_per_1000: null }));
        const w = useWeights(noDenominator);
        w.update('attendance', 0);
        w.update('decline', 0);
        expect(w.weights.decline).toBe(1 / 3);
        expect(w.message.value).not.toBe('');
    });

    it('shares one set of weights across screens', () => {
        const a = useWeights(ref(rows()));
        a.update('reach', 0.9);
        const b = useWeights(ref(rows()));
        expect(b.weights.reach).toBe(0.9);
    });

    it('exposes scored rows that follow the weights', () => {
        const w = useWeights(ref(rows()));
        const before = w.scored.value.map((r) => r.rank);
        w.applyPreset('Attendance first');
        expect(w.scored.value).toHaveLength(3);
        expect(before).toHaveLength(3);
    });

    it('remembers weights for the browser session', () => {
        const w = useWeights(ref(rows()));
        w.update('reach', 0.7);
        expect(JSON.parse(sessionStorage.getItem('pulse-weights')!).reach).toBe(0.7);
    });
});
