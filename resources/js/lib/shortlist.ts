import { ScoredRow } from './scoring';

export interface Shortlist {
    picks: ScoredRow[];
    capped: boolean;
}

/** Top `slots` ranked municipalities. `capped` is true when more slots were asked for than can be ranked. */
export function shortlist(scored: ScoredRow[], slots: number): Shortlist {
    const ranked = scored
        .filter((r) => r.rank !== null)
        .sort((a, b) => a.rank! - b.rank! || a.psgc.localeCompare(b.psgc));
    const n = Math.max(0, Math.floor(slots));
    return { picks: ranked.slice(0, n), capped: n > ranked.length };
}
