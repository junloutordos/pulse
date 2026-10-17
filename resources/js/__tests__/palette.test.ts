import { describe, expect, it } from 'vitest';
import { buildItems, normalize, searchItems } from '../lib/palette';

const munis = [
    { psgc: '1', name: 'Tagana-An', province: 'Surigao del Norte' },
    { psgc: '2', name: 'City of Butuan', province: 'Agusan del Norte' },
    { psgc: '3', name: 'Carmen', province: 'Agusan del Norte' },
    { psgc: '4', name: 'Carmen', province: 'Surigao del Sur' },
    { psgc: '5', name: 'Cantilan', province: 'Surigao del Sur' },
];
const items = buildItems(munis);

describe('normalize', () => {
    it('lowercases, drops accents and punctuation, and collapses spaces', () => {
        expect(normalize('  Taganá-An!! ')).toBe('tagana an');
    });
});

describe('searchItems', () => {
    it('lists the screens when nothing is typed', () => {
        const r = searchItems('', items);
        expect(r.every((i) => i.kind === 'screen')).toBe(true);
        expect(r.map((i) => i.label)).toContain('Priority list');
    });

    it('finds a municipality by the start of its name, ignoring punctuation', () => {
        expect(searchItems('tagana an', items)[0].label).toBe('Tagana-An');
        expect(searchItems('tagana', items)[0].label).toBe('Tagana-An');
    });

    it('matches a word inside the name, so "Butuan" finds "City of Butuan"', () => {
        expect(searchItems('butuan', items)[0].label).toBe('City of Butuan');
    });

    it('shows the province so two municipalities with the same name can be told apart', () => {
        const r = searchItems('carmen', items);
        expect(r.map((i) => i.hint)).toEqual(['Agusan del Norte', 'Surigao del Sur']);
    });

    it('finds municipalities by province', () => {
        const r = searchItems('surigao del sur', items);
        expect(r.map((i) => i.label).sort()).toEqual(['Cantilan', 'Carmen']);
    });

    it('ranks a prefix above a later match and respects the limit', () => {
        expect(searchItems('ca', items, 2)).toHaveLength(2);
        expect(searchItems('ca', items)[0].label.toLowerCase().startsWith('ca')).toBe(true);
    });

    it('finds screens by name', () => {
        expect(searchItems('map', items)[0]).toMatchObject({ kind: 'screen', route: 'map' });
    });

    it('returns nothing when nothing matches', () => {
        expect(searchItems('zzz', items)).toEqual([]);
    });
});
