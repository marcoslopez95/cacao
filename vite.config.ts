import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { defineConfig } from 'vitest/config';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
    ],
    test: {
        environment: 'jsdom',
        setupFiles: ['tests/js/setup.ts'],
        include: ['tests/js/**/*.test.ts'],
        coverage: {
            provider: 'v8',
            // lcov consumido por SonarQube (sonar.javascript.lcov.reportPaths)
            reporter: ['lcov', 'text'],
            reportsDirectory: 'coverage',
            include: ['resources/js/**/*.{ts,vue}'],
            exclude: [
                'resources/js/app.ts',
                'resources/js/actions/**',
                'resources/js/routes/**',
            ],
        },
    },
});
