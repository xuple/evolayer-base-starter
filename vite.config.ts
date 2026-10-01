import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { fontsource } from 'laravel-vite-plugin/fonts';
import { defineConfig, loadEnv } from 'vite';
import type { ServerOptions } from 'vite';

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

function resolveDevServerOrigin(rawOrigin: string | undefined): URL | null {
    const trimmedOrigin = rawOrigin?.trim();

    if (!trimmedOrigin) {
        return null;
    }

    let devServerOrigin: URL;

    try {
        devServerOrigin = new URL(trimmedOrigin);
    } catch {
        throw new Error(
            `VITE_DEV_SERVER_ORIGIN must be an absolute http(s) origin when set; received "${rawOrigin}".`,
        );
    }

    const hasPath = devServerOrigin.pathname !== '/';
    const hasExtraParts =
        devServerOrigin.username !== '' ||
        devServerOrigin.password !== '' ||
        hasPath ||
        devServerOrigin.search !== '' ||
        devServerOrigin.hash !== '';

    if (
        !['http:', 'https:'].includes(devServerOrigin.protocol) ||
        hasExtraParts
    ) {
        throw new Error(
            `VITE_DEV_SERVER_ORIGIN must be an http(s) origin without credentials, path, query, or fragment; received "${rawOrigin}".`,
        );
    }

    return devServerOrigin;
}

function resolveDevServerOriginPort(devServerOrigin: URL): number {
    if (devServerOrigin.port) {
        return Number(devServerOrigin.port);
    }

    return devServerOrigin.protocol === 'https:' ? 443 : 80;
}

function resolveInertiaSsrListener(rawUrl: string | undefined): {
    host: string;
    port: number;
} {
    const ssrUrlValue = rawUrl?.trim() || 'http://127.0.0.1:13714';
    let ssrUrl: URL;

    try {
        ssrUrl = new URL(ssrUrlValue);
    } catch {
        throw new Error(
            `INERTIA_SSR_URL must be an absolute http URL when set; received "${rawUrl}".`,
        );
    }

    const hasExtraParts =
        ssrUrl.username !== '' ||
        ssrUrl.password !== '' ||
        ssrUrl.pathname !== '/' ||
        ssrUrl.search !== '' ||
        ssrUrl.hash !== '';

    if (
        ssrUrl.protocol !== 'http:' ||
        ssrUrl.hostname === '' ||
        hasExtraParts
    ) {
        throw new Error(
            `INERTIA_SSR_URL must be an http URL without credentials, path, query, or fragment; received "${rawUrl}".`,
        );
    }

    const port = ssrUrl.port === '' ? 80 : Number(ssrUrl.port);

    if (!Number.isInteger(port) || port < 1 || port > 65535) {
        throw new Error(
            `INERTIA_SSR_URL must resolve to a port between 1 and 65535; received "${rawUrl}".`,
        );
    }

    const host =
        ssrUrl.hostname.startsWith('[') && ssrUrl.hostname.endsWith(']')
            ? ssrUrl.hostname.slice(1, -1)
            : ssrUrl.hostname;

    return { host, port };
}

function resolveDevServer(
    env: Record<string, string>,
): ServerOptions | undefined {
    const devServerPort = resolveDevServerPort(env.VITE_DEV_SERVER_PORT);
    const devServerOrigin = resolveDevServerOrigin(env.VITE_DEV_SERVER_ORIGIN);

    if (devServerOrigin !== null && devServerPort === null) {
        throw new Error(
            'VITE_DEV_SERVER_ORIGIN requires VITE_DEV_SERVER_PORT so the reverse proxy has a fixed local Vite target.',
        );
    }

    if (devServerPort === null) {
        return undefined;
    }

    const server: ServerOptions = {
        host: '127.0.0.1',
        port: devServerPort,
        strictPort: true,
    };

    if (devServerOrigin === null) {
        return server;
    }

    return {
        ...server,
        origin: devServerOrigin.origin,
        hmr: {
            clientPort: resolveDevServerOriginPort(devServerOrigin),
            host: devServerOrigin.hostname,
            path: '/vite-hmr',
            protocol: devServerOrigin.protocol === 'https:' ? 'wss' : 'ws',
        },
    };
}

export default defineConfig(({ command, mode }) => {
    const env = loadEnv(mode, process.cwd(), '');
    const devServer = command === 'serve' ? resolveDevServer(env) : undefined;
    const inertiaSsrListener = resolveInertiaSsrListener(env.INERTIA_SSR_URL);

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
        // Bundle all SSR dependencies into bootstrap/ssr so the built SSR
        // server is self-contained. Slim deployment images that copy
        // bootstrap/ssr WITHOUT node_modules otherwise FATAL on boot
        // (ERR_MODULE_NOT_FOUND: react) and Inertia silently falls back to
        // client-side rendering. Harmless when node_modules is present.
        ssr: { noExternal: true },
        ...(devServer === undefined ? {} : { server: devServer }),
        plugins: [
            laravel({
                input: ['resources/css/app.css', 'resources/js/app.tsx'],
                refresh: true,
                fonts: [
                    fontsource('Instrument Sans', {
                        subsets: ['latin', 'latin-ext'],
                        weights: [400, 500, 600],
                    }),
                ],
            }),
            inertia({ ssr: inertiaSsrListener }),
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
