import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig } from 'vite';

const vendorChunks = [
    ['react', ['/node_modules/react/', '/node_modules/react-dom/']],
    ['inertia', ['/node_modules/@inertiajs/react/']],
    ['icons', ['/node_modules/lucide-react/']],
    [
        'ui',
        [
            '/node_modules/@headlessui/react/',
            '/node_modules/@radix-ui/',
            '/node_modules/cmdk/',
            '/node_modules/sonner/',
        ],
    ],
] as const;

export default defineConfig({
    build: {
        rolldownOptions: {
            output: {
                manualChunks: (id) =>
                    vendorChunks.find(([, modules]) =>
                        modules.some((modulePath) => id.includes(modulePath)),
                    )?.[0],
            },
        },
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.tsx'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        react({
            babel: {
                plugins: ['babel-plugin-react-compiler'],
            },
        }),
        tailwindcss(),
        wayfinder({
            formVariants: true,
        }),
    ],
});
