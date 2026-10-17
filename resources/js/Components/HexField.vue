<script setup lang="ts">
// A quiet field of hexagons: the same shape the tile map uses. Decorative only.
const COLS = 22, ROWS = 30, R = 15;
const tones = ['var(--q2)', 'var(--q3)', 'var(--q4)', 'transparent', 'transparent', 'transparent', 'transparent'];
const cells = Array.from({ length: COLS * ROWS }, (_, i) => {
    const c = i % COLS, r = Math.floor(i / COLS);
    const x = (c + (r % 2) * 0.5) * R * Math.sqrt(3) + R, y = r * R * 1.5 + R;
    const seed = Math.sin(i * 12.9898) * 43758.5453;
    const tone = tones[Math.floor((seed - Math.floor(seed)) * tones.length)];
    return { x, y, tone, keep: (c * 3 + r * 7) % 4 !== 0 };
}).filter((c) => c.keep);
const pts = (x: number, y: number) => Array.from({ length: 6 }, (_, k) => `${(x + (R - 2.5) * Math.cos(Math.PI / 3 * k + Math.PI / 6)).toFixed(1)},${(y + (R - 2.5) * Math.sin(Math.PI / 3 * k + Math.PI / 6)).toFixed(1)}`).join(' ');
</script>

<template>
    <svg :viewBox="`0 0 ${COLS * R * 1.74} ${ROWS * R * 1.5 + R}`" class="h-full w-full" aria-hidden="true" preserveAspectRatio="xMidYMid slice">
        <polygon v-for="(c, i) in cells" :key="i" :points="pts(c.x, c.y)" :fill="c.tone" :stroke="c.tone === 'transparent' ? 'var(--shell-ink)' : 'none'" stroke-opacity="0.12" opacity="0.9" />
    </svg>
</template>
