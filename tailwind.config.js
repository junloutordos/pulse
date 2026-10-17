import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
    ],

    theme: {
        extend: {
            colors: {
                bg: 'var(--bg)',
                surface: 'var(--surface)',
                'surface-2': 'var(--surface-2)',
                shell: 'var(--shell)',
                'shell-ink': 'var(--shell-ink)',
                'accent-soft': 'var(--accent-soft)',
                ink: 'var(--ink)',
                muted: 'var(--muted)',
                line: 'var(--line)',
                accent: 'var(--accent)',
                'accent-ink': 'var(--accent-ink)',
                ok: 'var(--ok)',
                'ok-bg': 'var(--ok-bg)',
                warn: 'var(--warn)',
                'warn-bg': 'var(--warn-bg)',
                crit: 'var(--crit)',
                'crit-bg': 'var(--crit-bg)',
                q0: 'var(--q0)',
                q1: 'var(--q1)',
                q2: 'var(--q2)',
                q3: 'var(--q3)',
                q4: 'var(--q4)',
                land: 'var(--land)',
                sea: 'var(--sea)',
            },
            fontFamily: {
                display: 'var(--font-display)',
                body: 'var(--font-body)',
                sans: ['var(--font-body)', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                sm: 'var(--radius-sm)',
                DEFAULT: 'var(--radius-sm)',
                md: 'var(--radius)',
                lg: 'var(--radius-lg)',
            },
        },
    },

    plugins: [forms],
};
