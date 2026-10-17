export type Ranks = Map<string, number>;

/** Places gained since the last ranking: positive means moved up (a smaller rank number). Unchanged and unmatched rows are omitted. */
export function rankDeltas(prev: Ranks | null, next: Ranks): Record<string, number> {
    const out: Record<string, number> = {};
    if (!prev) return out;
    for (const [psgc, rank] of next) {
        const before = prev.get(psgc);
        if (before !== undefined && before !== rank) out[psgc] = before - rank;
    }
    return out;
}

export function biggestMover(prev: Ranks | null, next: Ranks): { psgc: string; from: number; to: number } | null {
    const deltas = rankDeltas(prev, next);
    let best: string | null = null;
    for (const psgc of Object.keys(deltas)) if (best === null || Math.abs(deltas[psgc]) > Math.abs(deltas[best])) best = psgc;
    return best === null ? null : { psgc: best, from: prev!.get(best)!, to: next.get(best)! };
}
