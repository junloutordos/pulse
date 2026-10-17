import { AllZeroWeightsError, IndicatorRow, priorityScores, Weights } from './scoring';

export const PRESETS: Record<string, Weights> = {
    Balanced: { reach: 1 / 3, attendance: 1 / 3, decline: 1 / 3 },
    'Reach first': { reach: 0.6, attendance: 0.2, decline: 0.2 },
    'Attendance first': { reach: 0.25, attendance: 0.55, decline: 0.2 },
};

export interface RankBand {
    lo: number;
    hi: number;
    stable: boolean;
}

const MAX_BAND_SPAN = 4;
const TOP_RANKS = 10;

/** Best and worst rank across presets. Stable means a narrow band whose worst rank is still in the top 10. */
export function rankBand(rows: IndicatorRow[], presets: Record<string, Weights>, psgc: string): RankBand | null {
    const ranks: number[] = [];
    for (const weights of Object.values(presets)) {
        try {
            const rank = priorityScores(rows, weights).find((r) => r.psgc === psgc)?.rank;
            if (rank != null) ranks.push(rank);
        } catch (e) {
            if (!(e instanceof AllZeroWeightsError)) throw e;
        }
    }
    if (ranks.length === 0) return null;
    const lo = Math.min(...ranks);
    const hi = Math.max(...ranks);
    return { lo, hi, stable: hi - lo <= MAX_BAND_SPAN && hi <= TOP_RANKS };
}
