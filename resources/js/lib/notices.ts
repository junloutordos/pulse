import { IndicatorRow } from './scoring';

/** Plain-language notices for indicators the whole dataset lacks, so a switched-off component is never a silent blank. */
export function datasetNotices(rows: IndicatorRow[]): string[] {
    const shown = rows.filter((r) => r.status === 'ok' || r.status === 'low');
    if (shown.length === 0) return [];
    const has = (column: 'rate_per_1000' | 'attendance' | 'change') => shown.some((r) => r[column] !== null);
    const notices: string[] = [];
    if (!has('rate_per_1000')) notices.push('This dataset has no Grade 6 population, so applications per 1,000 and the reach component are switched off.');
    if (!has('attendance')) notices.push('This dataset has no examinee counts, so exam attendance and the attendance component are switched off.');
    if (!has('change')) notices.push('This dataset has only one cycle, so change versus the prior cycle and the decline component are switched off.');
    return notices;
}
