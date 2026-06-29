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
