import { spawnSync } from 'node:child_process';
import { mkdtempSync, readFileSync, writeFileSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join } from 'node:path';
import { describe, expect, it } from 'vitest';

const css = readFileSync('resources/css/tokens.css', 'utf8');

function blockAfter(marker: string): string {
    const start = css.indexOf(marker);
    if (start < 0) throw new Error(`missing ${marker}`);
    let depth = 0;
    for (let i = css.indexOf('{', start); i < css.length; i++) {
        if (css[i] === '{') depth++;
        if (css[i] === '}' && --depth === 0) return css.slice(css.indexOf('{', start) + 1, i);
    }
    throw new Error(`unterminated ${marker}`);
}

const declarations = (block: string) =>
    new Map([...block.matchAll(/--([\w-]+)\s*:\s*([^;]+);/g)].map((m) => [m[1], m[2].trim()] as const));

const light = declarations(blockAfter(':root {'));
const systemDark = declarations(blockAfter(':root:not([data-theme="light"])'));
const explicitDark = declarations(blockAfter(':root[data-theme="dark"]'));

const COLORS = ['bg', 'surface', 'surface-2', 'shell', 'shell-ink', 'ink', 'muted', 'line', 'accent', 'accent-ink', 'accent-soft',
    'ok', 'ok-bg', 'warn', 'warn-bg', 'crit', 'crit-bg', 'q0', 'q1', 'q2', 'q3', 'q4', 'land', 'sea'];

describe('design tokens', () => {
    it('defines every colour token in light, system-dark and explicit-dark blocks', () => {
        for (const [name, map] of [['light', light], ['system dark', systemDark], ['explicit dark', explicitDark]] as const) {
            for (const token of COLORS) expect(map.has(token), `${name} is missing --${token}`).toBe(true);
        }
    });

    it('keeps the two dark blocks identical so the toggle and the OS setting agree', () => {
        expect([...systemDark.entries()]).toEqual([...explicitDark.entries()]);
    });

    it('defines type roles and radius once, on the light block', () => {
        for (const token of ['font-display', 'font-body', 'radius', 'radius-sm', 'radius-lg']) expect(light.has(token)).toBe(true);
    });

    it('uses a sans-serif display face, not a serif', () => {
        const display = light.get('font-display')!.replace(/sans-serif/g, '');
        expect(display).not.toMatch(/serif/i);
    });
});

describe('contrast check', () => {
    it('passes for the shipped tokens', () => {
        const run = spawnSync('node', ['scripts/check-contrast.mjs'], { encoding: 'utf8' });
        expect(run.status, run.stdout + run.stderr).toBe(0);
    });

    it('fails and names the pair when a text colour is too faint', () => {
        const dir = mkdtempSync(join(tmpdir(), 'tokens-'));
        const bad = css.replace(/--muted:\s*#[0-9a-fA-F]{6};/, '--muted: #c8d0d6;');
        writeFileSync(join(dir, 'tokens.css'), bad);
        const run = spawnSync('node', ['scripts/check-contrast.mjs', join(dir, 'tokens.css')], { encoding: 'utf8' });
        expect(run.status).not.toBe(0);
        expect(run.stdout + run.stderr).toContain('muted on bg');
    });
});
