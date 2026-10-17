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
