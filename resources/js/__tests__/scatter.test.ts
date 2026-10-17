import { describe, expect, it } from 'vitest';
import { nearestPoint, pointsInRect, quadrantOf, ScatterPoint } from '../lib/scatter';

const pts: ScatterPoint[] = [
    { psgc: 'A', x: 10, y: 0.6 },
    { psgc: 'B', x: 30, y: 0.9 },
    { psgc: 'C', x: 12, y: 0.95 },
    { psgc: 'D', x: 35, y: 0.55 },
];
const medians = { x: 20, y: 0.8 };

describe('quadrantOf', () => {
    it('names the four corners against the regional medians', () => {
        expect(quadrantOf(pts[0], medians)).toBe('low-low');
        expect(quadrantOf(pts[1], medians)).toBe('high-high');
        expect(quadrantOf(pts[2], medians)).toBe('low-apply-high-attend');
        expect(quadrantOf(pts[3], medians)).toBe('high-apply-low-attend');
    });

    it('treats a value exactly on a median as the higher side, so a point is never in two quadrants', () => {
        expect(quadrantOf({ psgc: 'E', x: 20, y: 0.8 }, medians)).toBe('high-high');
    });
});

describe('pointsInRect', () => {
    it('returns the points inside, whichever corner the drag started from', () => {
        expect(pointsInRect(pts, { x0: 5, y0: 0.5, x1: 15, y1: 1 }).map((p) => p.psgc)).toEqual(['A', 'C']);
        expect(pointsInRect(pts, { x0: 15, y0: 1, x1: 5, y1: 0.5 }).map((p) => p.psgc)).toEqual(['A', 'C']);
    });

    it('includes points on the edge and returns nothing for an empty rectangle', () => {
        expect(pointsInRect(pts, { x0: 10, y0: 0.6, x1: 10, y1: 0.6 }).map((p) => p.psgc)).toEqual(['A']);
        expect(pointsInRect(pts, { x0: 100, y0: 100, x1: 101, y1: 101 })).toEqual([]);
    });
});

describe('nearestPoint', () => {
    const screen = pts.map((p) => ({ ...p, x: p.x * 10, y: 100 - p.y * 100 }));

    it('finds the closest point within the reach, in screen units', () => {
        expect(nearestPoint(screen, 102, 41, 20)?.psgc).toBe('A');
    });

    it('returns null when nothing is close enough', () => {
        expect(nearestPoint(screen, 500, 500, 20)).toBeNull();
    });

    it('prefers the nearer of two candidates', () => {
        const close = [{ psgc: 'P', x: 10, y: 10 }, { psgc: 'Q', x: 14, y: 10 }];
        expect(nearestPoint(close, 13, 10, 20)?.psgc).toBe('Q');
    });
});
