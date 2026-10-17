export interface ScatterPoint {
    psgc: string;
    x: number;
    y: number;
}

export type Quadrant = 'low-low' | 'high-high' | 'low-apply-high-attend' | 'high-apply-low-attend';

export const QUADRANT_LABEL: Record<Quadrant, string> = {
    'low-low': 'Low on both',
    'high-high': 'Strong on both',
    'low-apply-high-attend': 'Few apply, most sit the exam',
    'high-apply-low-attend': 'Many apply, many miss the exam',
};

/** x is applications per 1,000, y is attendance. A value exactly on a median counts as the higher side. */
export function quadrantOf(p: ScatterPoint, m: { x: number; y: number }): Quadrant {
    const highApply = p.x >= m.x;
    const highAttend = p.y >= m.y;
    if (highApply && highAttend) return 'high-high';
    if (!highApply && !highAttend) return 'low-low';
    return highApply ? 'high-apply-low-attend' : 'low-apply-high-attend';
}

export function pointsInRect<T extends ScatterPoint>(points: T[], r: { x0: number; y0: number; x1: number; y1: number }): T[] {
    const [xa, xb] = [Math.min(r.x0, r.x1), Math.max(r.x0, r.x1)];
    const [ya, yb] = [Math.min(r.y0, r.y1), Math.max(r.y0, r.y1)];
    return points.filter((p) => p.x >= xa && p.x <= xb && p.y >= ya && p.y <= yb);
}

/** Closest point to a cursor position, in the same (screen) units as the points, or null when none is within `reach`. */
export function nearestPoint<T extends ScatterPoint>(points: T[], x: number, y: number, reach: number): T | null {
    let best: T | null = null;
    let bestD = reach;
    for (const p of points) {
        const d = Math.hypot(p.x - x, p.y - y);
        if (d <= bestD) {
            best = p;
            bestD = d;
        }
    }
    return best;
}
