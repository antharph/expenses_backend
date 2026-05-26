import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    let hmrHost = 'localhost';
    const devServerUrl = env.VITE_DEV_SERVER_URL;
    if (devServerUrl) {
        try {
            hmrHost = new URL(devServerUrl).hostname;
        } catch {
            // keep localhost
        }
    }

    return {
        server: {
            host: '0.0.0.0',
            port: 5173,
            hmr: {
                host: hmrHost,
            },
        },
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.ts'],
                refresh: true,
                fonts: [
                    bunny('Instrument Sans', {
                        weights: [400, 500, 600],
                    }),
                ],
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
                command:
                    env.WAYFINDER_COMMAND ||
                    (env.APP_ENV === 'production'
                        ? 'php artisan wayfinder:generate'
                        : 'docker exec -w /var/www/expenses codev_php8.4-webserver-4 php artisan wayfinder:generate'),
            }),
        ],
    };
});
