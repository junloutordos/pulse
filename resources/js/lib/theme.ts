export type Theme = 'system' | 'light' | 'dark';

export const THEME_KEY = 'pulse-theme';

export function getStoredTheme(): Theme {
    try {
        const value = localStorage.getItem(THEME_KEY);
        return value === 'light' || value === 'dark' ? value : 'system';
    } catch {
        return 'system';
    }
}

export function applyTheme(theme: Theme): void {
    const root = document.documentElement;
    if (theme === 'system') root.removeAttribute('data-theme');
    else root.setAttribute('data-theme', theme);
}

export function setTheme(theme: Theme): void {
    try {
        localStorage.setItem(THEME_KEY, theme);
    } catch {
        // Storage can be blocked; the choice then lasts for this page view only.
    }
    applyTheme(theme);
}
