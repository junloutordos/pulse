import { onBeforeUnmount, onMounted, ref } from 'vue';

const NAMES = ['bg', 'surface', 'ink', 'muted', 'line', 'accent', 'crit', 'warn', 'q0', 'q1', 'q2', 'q3', 'q4', 'land', 'sea'] as const;

export type ThemeColors = Record<(typeof NAMES)[number], string>;

export function readColors(): ThemeColors {
    const style = getComputedStyle(document.documentElement);
    return Object.fromEntries(NAMES.map((n) => [n, style.getPropertyValue(`--${n}`).trim()])) as ThemeColors;
}

/** Resolved token colours for canvas and SVG drawing. Updates when the theme toggle or the OS setting changes. */
export function useThemeColors() {
    const colors = ref<ThemeColors>(readColors());
    const refresh = () => (colors.value = readColors());
    let observer: MutationObserver | null = null;
    const media = typeof matchMedia === 'function' ? matchMedia('(prefers-color-scheme: dark)') : null;

    onMounted(() => {
        refresh();
        observer = new MutationObserver(refresh);
        observer.observe(document.documentElement, { attributes: true, attributeFilter: ['data-theme'] });
        media?.addEventListener('change', refresh);
    });
    onBeforeUnmount(() => {
        observer?.disconnect();
        media?.removeEventListener('change', refresh);
    });
    return colors;
}
