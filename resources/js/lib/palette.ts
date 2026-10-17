export interface PaletteItem {
    kind: 'screen' | 'municipality';
    id: string;
    label: string;
    hint: string;
    route?: string;
    psgc?: string;
    haystack: string;
}

export const SCREENS = [
    { label: 'Data trust', route: 'trust', hint: 'Step 1' },
    { label: 'Map', route: 'map', hint: 'Step 2' },
    { label: 'Pipeline', route: 'pipeline', hint: 'Step 3' },
    { label: 'Priority list', route: 'priority', hint: 'Step 4' },
    { label: 'Planner', route: 'planner', hint: 'Step 5' },
    { label: 'Brief', route: 'brief', hint: 'Step 6' },
];

export const normalize = (s: string): string =>
    s.normalize('NFD').replace(/[̀-ͯ]/g, '').toLowerCase().replace(/[^a-z0-9]+/g, ' ').trim();

export function buildItems(municipalities: { psgc: string; name: string; province: string }[]): PaletteItem[] {
    return [
        ...SCREENS.map((s) => ({ kind: 'screen' as const, id: `screen:${s.route}`, label: s.label, hint: s.hint, route: s.route, haystack: normalize(s.label) })),
        ...municipalities.map((m) => ({ kind: 'municipality' as const, id: m.psgc, label: m.name, hint: m.province, psgc: m.psgc, haystack: normalize(`${m.name} ${m.province}`) })),
    ];
}

/** 0 = name starts with the query, 1 = a word in the name starts with it, 2 = the name contains it, 3 = only the rest (province) does. */
function score(item: PaletteItem, q: string): number | null {
    const name = normalize(item.label);
    if (name.startsWith(q)) return 0;
    if (name.split(' ').some((w) => w.startsWith(q))) return 1;
    if (name.includes(q)) return 2;
    if (item.haystack.includes(q)) return 3;
    return null;
}

export function searchItems(query: string, items: PaletteItem[], limit = 8): PaletteItem[] {
    const q = normalize(query);
    if (!q) return items.filter((i) => i.kind === 'screen').slice(0, limit);
    return items
        .map((item) => ({ item, s: score(item, q) }))
        .filter((x): x is { item: PaletteItem; s: number } => x.s !== null)
        .sort((a, b) => a.s - b.s || (a.item.kind === b.item.kind ? 0 : a.item.kind === 'screen' ? -1 : 1) || a.item.label.localeCompare(b.item.label) || a.item.hint.localeCompare(b.item.hint))
        .slice(0, limit)
        .map((x) => x.item);
}
