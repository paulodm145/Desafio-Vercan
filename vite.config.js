import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/fornecedores/index.js',
                'resources/js/fornecedores-grid/index.js',
            ],
            refresh: true,
        }),
    ],
    build: {
        // Vite 8's default CSS minifier mangles bootstrap-icons' escaped glyph
        // content (e.g. `\f64d`) down to an empty string, breaking every icon.
        // esbuild would fix it but isn't bundled with Rolldown-based Vite 8
        // by default, so minification is off instead of adding that dependency
        // just for this.
        cssMinify: false,
    },
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
