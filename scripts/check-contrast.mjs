// Fails (exit 1) when a text/background token pair is below WCAG AA (4.5:1) in either theme.
import { readFileSync } from 'node:fs';

const file = process.argv[2] ?? 'resources/css/tokens.css';
const css = readFileSync(file, 'utf8');

function blockAfter(marker) {
    const start = css.indexOf(marker);
    if (start < 0) throw new Error(`${file}: missing ${marker}`);
    const open = css.indexOf('{', start);
    let depth = 0;
    for (let i = open; i < css.length; i++) {
        if (css[i] === '{') depth++;
        if (css[i] === '}' && --depth === 0) return css.slice(open + 1, i);
    }
    throw new Error(`${file}: unterminated ${marker}`);
}

const declarations = (block) =>
    Object.fromEntries([...block.matchAll(/--([\w-]+)\s*:\s*(#[0-9a-fA-F]{6})\s*;/g)].map((m) => [m[1], m[2]]));

const light = declarations(blockAfter(':root {'));
const dark = { ...light, ...declarations(blockAfter(':root[data-theme="dark"]')) };

const channel = (v) => (v <= 0.03928 ? v / 12.92 : ((v + 0.055) / 1.055) ** 2.4);
const luminance = (hex) => {
    const [r, g, b] = [1, 3, 5].map((i) => channel(parseInt(hex.slice(i, i + 2), 16) / 255));
    return 0.2126 * r + 0.7152 * g + 0.0722 * b;
};
const ratio = (a, b) => {
    const [hi, lo] = [luminance(a), luminance(b)].sort((x, y) => y - x);
    return (hi + 0.05) / (lo + 0.05);
};

const PAIRS = [
    ['ink', 'bg'], ['ink', 'surface'], ['muted', 'bg'], ['muted', 'surface'], ['accent', 'bg'], ['accent', 'surface'],
    ['accent-ink', 'accent'], ['ok', 'ok-bg'], ['warn', 'warn-bg'], ['crit', 'crit-bg'], ['crit', 'surface'],
];

let failed = false;
for (const [theme, tokens] of [['light', light], ['dark', dark]]) {
    for (const [fg, bg] of PAIRS) {
        const value = ratio(tokens[fg], tokens[bg]);
        if (value < 4.5) {
            failed = true;
            console.error(`FAIL ${fg} on ${bg} (${theme}): ${value.toFixed(2)}:1, needs 4.5:1`);
        }
    }
}
if (failed) process.exit(1);
console.log('Contrast OK: every text pair is at least 4.5:1 in light and dark.');
