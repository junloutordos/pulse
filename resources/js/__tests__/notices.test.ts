import { describe, expect, it } from 'vitest';
import { datasetNotices } from '../lib/notices';
import { IndicatorRow } from '../lib/scoring';

const row = (over: Partial<IndicatorRow> = {}): IndicatorRow => ({ psgc: 'A', status: 'ok', rate_per_1000: 10, attendance: 0.9, change: -0.1, ...over });

describe('datasetNotices', () => {
    it('is quiet when every indicator exists', () => {
        expect(datasetNotices([row(), row({ psgc: 'B' })])).toEqual([]);
    });

    it('says so when there is no Grade 6 population anywhere', () => {
        const notices = datasetNotices([row({ rate_per_1000: null }), row({ psgc: 'B', rate_per_1000: null })]);
        expect(notices).toHaveLength(1);
        expect(notices[0]).toMatch(/no Grade 6 population/i);
        expect(notices[0]).toMatch(/applications per 1,000/i);
    });

    it('says so when there is only one cycle', () => {
        const notices = datasetNotices([row({ change: null }), row({ psgc: 'B', change: null })]);
        expect(notices[0]).toMatch(/only one cycle/i);
    });

    it('ignores rows that are suppressed or missing', () => {
        const rows = [row({ status: 'suppressed', rate_per_1000: null, attendance: null, change: null }), row()];
        expect(datasetNotices(rows)).toEqual([]);
    });

    it('lists each missing indicator once', () => {
        const rows = [row({ rate_per_1000: null, change: null })];
        expect(datasetNotices(rows)).toHaveLength(2);
    });
});
