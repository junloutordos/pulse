export type Status = 'ok' | 'low' | 'suppressed' | 'missing';

export interface IndicatorRow {
    psgc: string;
    status: Status;
    rate_per_1000: number | null;
    attendance: number | null;
    change: number | null;
}

export type Weights = { reach: number; attendance: number; decline: number };

export interface ScoredRow {
    psgc: string;
    score: number | null;
    c_reach: number | null;
    c_attendance: number | null;
    c_decline: number | null;
    rank: number | null;
    not_ranked_reason: string | null;
}

export class AllZeroWeightsError extends Error {
    constructor(message = 'Weights are all zero for the components available in this dataset.') {
        super(message);
        this.name = 'AllZeroWeightsError';
    }
}

const LOW_SAMPLE_BELOW = 15;

const STATUS_REASON: Record<Exclude<Status, 'ok'>, string> = {
    low: `fewer than ${LOW_SAMPLE_BELOW} applicants`,
    suppressed: 'applicant count suppressed',
    missing: 'no data for this cycle',
};

// A low value on each indicator means higher priority. Keep in step with pipeline/src/pulse_pipeline/scoring.py.
const COMPONENTS = [
    { key: 'reach', column: 'rate_per_1000', out: 'c_reach' },
    { key: 'attendance', column: 'attendance', out: 'c_attendance' },
    { key: 'decline', column: 'change', out: 'c_decline' },
] as const;

function averageRanks(values: number[]): number[] {
    const order = values.map((v, i) => [v, i] as const).sort((a, b) => a[0] - b[0]);
    const ranks = new Array<number>(values.length);
    for (let start = 0; start < order.length; ) {
        let end = start;
        while (end + 1 < order.length && order[end + 1][0] === order[start][0]) end++;
        const average = (start + end) / 2 + 1;
        for (let k = start; k <= end; k++) ranks[order[k][1]] = average;
        start = end + 1;
    }
    return ranks;
}

function lowIsHighPriority(values: number[]): number[] {
    if (values.length < 2) return values.map(() => 1);
    return averageRanks(values).map((rank) => 1 - (rank - 1) / (values.length - 1));
}

export function priorityScores(rows: IndicatorRow[], w: Weights): ScoredRow[] {
    if (w.reach < 0 || w.attendance < 0 || w.decline < 0) throw new RangeError('Weights must not be negative.');
    if (w.reach + w.attendance + w.decline === 0) throw new AllZeroWeightsError();

    const basic = rows.map((r) => r.status === 'ok');
    const active = COMPONENTS.filter((c) => rows.some((r, i) => basic[i] && r[c.column] !== null));
    const weightSum = active.reduce((sum, c) => sum + w[c.key], 0);
    if (weightSum <= 0) throw new AllZeroWeightsError();

    const eligible = rows.map((r, i) => basic[i] && active.every((c) => r[c.column] !== null));
    const idx = rows.map((_, i) => i).filter((i) => eligible[i]);

    const result: ScoredRow[] = rows.map((r) => ({
        psgc: r.psgc, score: null, c_reach: null, c_attendance: null, c_decline: null, rank: null, not_ranked_reason: null,
    }));
    const totals = new Map<number, number>(idx.map((i) => [i, 0]));
    for (const c of active) {
        const comps = lowIsHighPriority(idx.map((i) => rows[i][c.column] as number));
        idx.forEach((i, n) => {
            result[i][c.out] = comps[n];
            totals.set(i, totals.get(i)! + w[c.key] * comps[n]);
        });
    }
    for (const i of idx) result[i].score = (100 * totals.get(i)!) / weightSum;
    for (const i of idx) result[i].rank = 1 + idx.filter((j) => result[j].score! > result[i].score!).length;

    rows.forEach((r, i) => {
        if (eligible[i]) return;
        result[i].not_ranked_reason = basic[i]
            ? `missing ${active.find((c) => r[c.column] === null)!.key}`
            : STATUS_REASON[r.status as Exclude<Status, 'ok'>];
    });
    return result;
}
