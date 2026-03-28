import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue'

const viteHost = process.env.VITE_DEV_SERVER_HOST || '0.0.0.0'
const viteHmrHost = process.env.VITE_HMR_HOST || 'localhost'
const vitePort = Number(process.env.VITE_PORT || 5173)

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/admin/bootstrap.js',
            ],
            refresh: true,
        }),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
    ],
    resolve: {
        alias: {
            '@': new URL('./resources/js', import.meta.url).pathname,
            vue: 'vue/dist/vue.esm-bundler.js',
        },
    },
    server: {
        host: viteHost,
        port: vitePort,
        strictPort: true,
        watch: {
            usePolling: true,
        },
        hmr: {
            host: viteHmrHost,
            port: vitePort,
        },
    },
});
