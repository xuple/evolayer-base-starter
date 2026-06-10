import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';

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

function resolveDevServerPort(rawPort: string | undefined): number | null {
    const trimmedPort = rawPort?.trim();

    if (!trimmedPort) {
        return null;
    }

    const devServerPort = Number(trimmedPort);

    if (
        !/^\d+$/.test(trimmedPort) ||
        !Number.isInteger(devServerPort) ||
        devServerPort < 1 ||
        devServerPort > 65535
    ) {
        throw new Error(
            `VITE_DEV_SERVER_PORT must be an integer between 1 and 65535 when set; received "${rawPort}".`,
        );
    }

    return devServerPort;
}

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devServerPort = resolveDevServerPort(env.VITE_DEV_SERVER_PORT);

    return {
        build: {
            rolldownOptions: {
                output: {
                    manualChunks: (id) =>
                        vendorChunks.find(([, modules]) =>
                            modules.some((modulePath) =>
                                id.includes(modulePath),
                            ),
                        )?.[0],
                },
            },
        },
        ...(devServerPort === null
            ? {}
            : {
                  server: {
                      host: '127.0.0.1',
                      port: devServerPort,
                      strictPort: true,
                  },
              }),
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
    };
});
