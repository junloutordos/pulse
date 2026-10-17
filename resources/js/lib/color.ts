export type Rgb = [number, number, number];

export function parseHex(hex: string): Rgb | null {
    const m = /^#?([0-9a-f]{6})$/i.exec(hex.trim());
    if (!m) return null;
    const n = parseInt(m[1], 16);
    return [(n >> 16) & 255, (n >> 8) & 255, n & 255];
}

const toHex = (c: Rgb) => `#${c.map((v) => Math.round(v).toString(16).padStart(2, '0')).join('')}`;

/** Linear blend of two hex colours; falls back to the target when either is not a plain hex colour. */
export function mixHex(from: string, to: string, t: number): string {
    const a = parseHex(from);
    const b = parseHex(to);
    if (!a || !b) return to;
    const k = Math.min(1, Math.max(0, t));
    return toHex([a[0] + (b[0] - a[0]) * k, a[1] + (b[1] - a[1]) * k, a[2] + (b[2] - a[2]) * k]);
}
