import { ScoredRow, Weights } from './scoring';

export const ACTIONS = {
    reach: 'Information and application campaign',
    attendance: 'Exam reminder and access review',
    decline: 'Follow-up with feeder schools on the decline',
} as const;

export const VERIFY_ACTION = 'Data verification with the PSHSS custodian';

const COMPONENTS = [
    ['reach', 'c_reach'],
    ['attendance', 'c_attendance'],
    ['decline', 'c_decline'],
] as const;

/** Rule-based suggestion: the component that contributes most to the weighted score decides the focus. Staff validate it. */
export function suggestedFocus(row: ScoredRow, w: Weights): string {
    if (row.score === null) return VERIFY_ACTION;
    let best: { key: keyof typeof ACTIONS; value: number } | null = null;
    for (const [key, field] of COMPONENTS) {
        const c = row[field];
        if (c === null) continue;
        const value = w[key] * c;
        if (best === null || value > best.value) best = { key, value };
    }
    return best ? ACTIONS[best.key] : VERIFY_ACTION;
}
