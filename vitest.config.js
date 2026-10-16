import { defineConfig } from 'vitest/config';

export default defineConfig({
    test: {
        include: ['resources/js/**/*.{test,spec}.js'],
        environment: 'jsdom',
        globals: true,
        setupFiles: ['fake-indexeddb/auto'],
    },
});
