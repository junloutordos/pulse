/** Upper edges of every bin but the last, taken from the sorted non-null values. */
export function quantileBreaks(values: (number | null)[], bins = 5): number[] {
    const sorted = values.filter((v): v is number => v !== null).sort((a, b) => a - b);
    if (sorted.length === 0) return [];
    return Array.from({ length: bins - 1 }, (_, i) => sorted[Math.floor(((i + 1) / bins) * (sorted.length - 1))]);
}

/** Bin index for a value, or null when there is no value so the municipality stays unfilled. */
export function binOf(value: number | null, breaks: number[]): number | null {
    if (value === null) return null;
    return breaks.filter((b) => value > b).length;
}

/** Readable ranges for each bin, e.g. ['≤ 3', '3–5', '5–7', '7–9', '> 9']. */
export function legendLabels(breaks: number[], format: (v: number) => string): string[] {
    if (breaks.length === 0) return [];
    return [
        `≤ ${format(breaks[0])}`,
        ...breaks.slice(1).map((b, i) => `${format(breaks[i])}–${format(b)}`),
        `> ${format(breaks[breaks.length - 1])}`,
    ];
}
