<?php

namespace App\Support\EvoLayer;

final class StarterSurfaceInventory
{
    /** @return list<string> */
    public static function registrationSources(): array
    {
        return [
            'resources/js/layouts/public-layout.tsx',
            'resources/js/pages/auth/login.tsx',
            'resources/js/pages/welcome.tsx',
        ];
    }

    /**
     * Starter-owned code must remain route-import-free for package examples.
     * Base owns the pages and routes themselves; this inventory deliberately
     * records only the host surfaces that can introduce compile-time coupling.
     *
     * @return list<string>
     */
    public static function packageExampleSources(): array
    {
        return ['resources/js/config/navigation.ts'];
    }

    /** @return list<string> */
    public static function frontendFingerprintPaths(): array
    {
        return [
            ...self::registrationSources(),
            ...self::packageExampleSources(),
            'resources/js/lib/page-surfaces.ts',
            'resources/js/app.tsx',
        ];
    }

    public static function packageExamplesAreSafelyReferenced(string $contents): bool
    {
        return ! str_contains($contents, '@/routes/evolayer')
            && ! str_contains($contents, '@/actions/Xuple/EvoLayer');
    }
}
