<?php

/*
| Regression guard for the non-destructive resync contract (Phase 5 adoption).
| The starter must drive resync through the manifest-safe `evolayer:resync`
| command (skip-modified by default) rather than the old force-publish path that
| clobbered host-customized stubs, and the landing pages must be branded from
| config rather than maintained as bespoke full-file overrides.
*/

function resyncScript(): string
{
    $composer = json_decode((string) file_get_contents(base_path('composer.json')), true);

    return implode("\n", $composer['scripts']['evolayer:resync'] ?? []);
}

test('the evolayer:resync composer script uses the safe artisan command', function () {
    expect(resyncScript())->toContain('@php artisan evolayer:resync');
});

test('the evolayer:resync script does not force-publish frontend stubs', function () {
    // vendor:publish of a frontend tag with --force clobbers host-customized
    // stubs (e.g. config/navigation.ts); the manifest-safe command must own
    // frontend resync instead.
    expect(resyncScript())
        ->not->toContain('evolayer-base-frontend')
        ->not->toContain('preserve-overrides');
});

test('managed landing pages render from brand config, not a full-file override', function () {
    foreach (['base.tsx'] as $page) {
        $path = base_path("resources/js/pages/evolayer/{$page}");

        if (! config('evolayer.base.examples.marketing_pages')) {
            expect($path)->not->toBeFile();

            continue;
        }

        $content = (string) file_get_contents($path);

        expect($content)
            ->toContain('useBrand')                   // branded from config / shared props
            ->not->toContain('_STARTER_OWNED_PAGE_');  // no bespoke override sentinel
    }
});
