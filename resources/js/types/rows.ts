import { IndicatorRow } from '@/lib/scoring';

export type Cluster = 'HH' | 'LL' | 'HL' | 'LH' | 'ns';

/** One municipality as sent by the server for the active dataset version. */
export interface MunicipalityRow extends IndicatorRow {
    name: string;
    province: string;
    lat: number;
    lon: number;
    applicants: number | null;
    examinees: number | null;
    qualifiers: number | null;
    grade6_pop: number | null;
    prior_applicants: number | null;
    lisa_cluster: Cluster | null;
}
