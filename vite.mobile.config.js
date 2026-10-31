import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';

// Standalone build for the Capacitor app: a plain index.html + bundled assets,
// not routed through Blade/laravel-vite-plugin like the web build.
export default defineConfig({
    root: 'resources/mobile',
    base: './',
    plugins: [
        vue(),
        tailwindcss(),
    ],
    build: {
        outDir: '../../dist-mobile',
        emptyOutDir: true,
    },
});
