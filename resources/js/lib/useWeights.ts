import { computed, reactive, Ref, ref } from 'vue';
import { PRESETS } from './rankBand';
import { AllZeroWeightsError, IndicatorRow, priorityScores, ScoredRow, Weights } from './scoring';

const STORAGE_KEY = 'pulse-weights';

function load(): Weights {
    try {
        const saved = JSON.parse(sessionStorage.getItem(STORAGE_KEY) ?? 'null');
        if (saved && ['reach', 'attendance', 'decline'].every((k) => typeof saved[k] === 'number' && saved[k] >= 0)) return saved;
    } catch {
        // Storage can be blocked; fall back to the balanced preset.
    }
    return { ...PRESETS.Balanced };
}

// One set of weights shared by every screen, so Priority, Planner and Brief agree.
const state = reactive<Weights>(load());

export function resetWeights(): void {
    Object.assign(state, PRESETS.Balanced);
}

function remember(): void {
    try {
        sessionStorage.setItem(STORAGE_KEY, JSON.stringify({ ...state }));
    } catch {
        // Weights then last for this page view only.
    }
}

export function useWeights(rows: Ref<IndicatorRow[]>) {
    const message = ref('');

    const usable = (w: Weights): boolean => {
        try {
            priorityScores(rows.value, w);
            return true;
        } catch (e) {
            if (e instanceof AllZeroWeightsError) return false;
            throw e;
        }
    };

    function commit(next: Weights, blocked: string): void {
        if (!usable(next)) {
            message.value = blocked;
            return;
        }
        message.value = '';
        Object.assign(state, next);
        remember();
    }

    function update(key: keyof Weights, value: number): void {
        if (!Number.isFinite(value)) return;
        commit({ ...state, [key]: Math.min(1, Math.max(0, value)) }, 'Keep at least one weight above zero for the components this dataset can score.');
    }

    function applyPreset(name: string): void {
        const preset = PRESETS[name];
        if (preset) commit({ ...preset }, 'This preset puts no weight on the components this dataset can score.');
    }

    const scored = computed<ScoredRow[]>(() => {
        try {
            return priorityScores(rows.value, state);
        } catch (e) {
            if (e instanceof AllZeroWeightsError) return [];
            throw e;
        }
    });

    return { weights: state, message, scored, update, applyPreset };
}
