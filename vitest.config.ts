import vue from '@vitejs/plugin-vue';
import { fileURLToPath } from 'node:url';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [vue()],
    resolve: {
        alias: { '@': fileURLToPath(new URL('./resources/js', import.meta.url)) },
    },
    test: {
        environment: 'jsdom',
        include: ['resources/js/**/*.test.ts'],
        // Node 25 ships its own localStorage that shadows jsdom's; turn it off so the DOM one is used.
        execArgv: ['--no-experimental-webstorage'],
    },
});
