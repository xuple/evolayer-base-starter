<?php

test('vite font setup uses local fontsource assets instead of remote providers', function () {
    $viteConfig = (string) file_get_contents(base_path('vite.config.ts'));
    $package = json_decode((string) file_get_contents(base_path('package.json')), true);

    expect($package['dependencies'] ?? [])
        ->toHaveKey('@fontsource/instrument-sans');

    expect($viteConfig)
        ->toContain("import { fontsource } from 'laravel-vite-plugin/fonts';")
        ->toContain("fontsource('Instrument Sans'")
        ->toContain("subsets: ['latin', 'latin-ext']")
        ->not->toContain("import { bunny } from 'laravel-vite-plugin/fonts';")
        ->not->toContain("bunny('Instrument Sans'")
        ->not->toContain("google('Instrument Sans'");
});

test('vite bundles SSR dependencies so slim images run SSR without node_modules', function () {
    // Deployment images that copy bootstrap/ssr without node_modules FATAL
    // on externalized deps (ERR_MODULE_NOT_FOUND: react) and silently fall
    // back to client-side rendering — first observed promoting a generated
    // app to a container stack.
    expect((string) file_get_contents(base_path('vite.config.ts')))
        ->toContain('ssr: { noExternal: true }');
});
