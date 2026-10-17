import { describe, expect, it } from 'vitest';
import { ACTIONS, suggestedFocus, VERIFY_ACTION } from '../lib/focus';
import { ScoredRow } from '../lib/scoring';

const row = (c: Partial<ScoredRow>): ScoredRow => ({
    psgc: 'A', score: 50, rank: 1, c_reach: 0, c_attendance: 0, c_decline: 0, not_ranked_reason: null, ...c,
});
const even = { reach: 1, attendance: 1, decline: 1 };

describe('suggestedFocus', () => {
    it('picks the component that contributes most to the weighted score', () => {
        expect(suggestedFocus(row({ c_attendance: 0.9, c_reach: 0.2 }), even)).toBe(ACTIONS.attendance);
        expect(suggestedFocus(row({ c_decline: 1 }), even)).toBe(ACTIONS.decline);
    });

    it('lets the weights change the answer', () => {
        const r = row({ c_reach: 0.5, c_attendance: 0.8 });
        expect(suggestedFocus(r, { reach: 3, attendance: 1, decline: 1 })).toBe(ACTIONS.reach);
        expect(suggestedFocus(r, { reach: 1, attendance: 3, decline: 1 })).toBe(ACTIONS.attendance);
    });

    it('prefers reach, then attendance, then decline on exact ties', () => {
        expect(suggestedFocus(row({ c_reach: 0.5, c_attendance: 0.5, c_decline: 0.5 }), even)).toBe(ACTIONS.reach);
    });

    it('skips components that do not apply', () => {
        expect(suggestedFocus(row({ c_reach: null, c_attendance: 0.4, c_decline: 0.1 }), even)).toBe(ACTIONS.attendance);
    });

    it('recommends data verification when the municipality is not ranked', () => {
        expect(suggestedFocus(row({ score: null, rank: null, not_ranked_reason: 'applicant count suppressed', c_reach: null, c_attendance: null, c_decline: null }), even)).toBe(VERIFY_ACTION);
    });
});
