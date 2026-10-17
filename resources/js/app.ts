import '@fontsource-variable/hanken-grotesk';
import '../css/app.css';

import { createInertiaApp } from '@inertiajs/vue3';
import { resolvePageComponent } from 'laravel-vite-plugin/inertia-helpers';
import { createApp, DefineComponent, h } from 'vue';
import AppLayout from './Layouts/AppLayout.vue';
import { ZiggyVue } from '../../vendor/tightenco/ziggy';

const APP_PAGES = new Set(['Dashboard', 'Trust', 'Map', 'Pipeline', 'Priority', 'Planner', 'Brief']);
const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

createInertiaApp({
    title: (title) => `${title} - ${appName}`,
    // The six workflow screens and the start page share one persistent layout, so the header and stepper stay mounted
    // while only the page changes. That is what lets the stepper indicator slide between steps.
    resolve: async (name) => {
        const page = (await resolvePageComponent(`./Pages/${name}.vue`, import.meta.glob<DefineComponent>('./Pages/**/*.vue'))) as unknown as { default: { layout?: unknown } };
        if (APP_PAGES.has(name) && !page.default.layout) page.default.layout = AppLayout;
        return page as never;
    },
    setup({ el, App, props, plugin }) {
        createApp({ render: () => h(App, props) })
            .use(plugin)
            .use(ZiggyVue)
            .mount(el);
    },
    progress: {
        color: '#4B5563',
    },
});
