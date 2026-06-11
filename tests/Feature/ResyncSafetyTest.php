<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResyncSafetyTest extends TestCase
{
    public function test_starter_owned_landing_pages_contain_resync_sentinel(): void
    {
        $starterOwnedPages = [
            'resources/js/pages/evolayer/about.tsx',
            'resources/js/pages/evolayer/home.tsx',
        ];

        foreach ($starterOwnedPages as $page) {
            $path = base_path($page);
            $this->assertFileExists($path, "Starter-owned landing page {$page} is missing.");

            $content = (string) file_get_contents($path);
            $this->assertStringContainsString(
                '_STARTER_OWNED_PAGE_',
                $content,
                "Starter-owned landing page {$page} is missing the _STARTER_OWNED_PAGE_ sentinel. "
                    .'This sentinel is required so `composer evolayer:resync` can verify the safe publish path '
                    .'preserved the starter override. Re-apply the starter override and '
                    .'ensure the sentinel comment is present.',
            );
        }
    }

    public function test_resync_safety_script_exists_and_is_executable(): void
    {
        $scriptPath = base_path('scripts/resync-starter-pages-check.sh');

        $this->assertFileExists($scriptPath);
        $this->assertTrue(
            is_executable($scriptPath),
            'scripts/resync-starter-pages-check.sh must be executable.',
        );
    }

    public function test_resync_safety_script_passes_on_current_repo_state(): void
    {
        $scriptPath = base_path('scripts/resync-starter-pages-check.sh');

        exec("bash {$scriptPath} 2>&1", $output, $exitCode);

        $this->assertSame(
            0,
            $exitCode,
            'scripts/resync-starter-pages-check.sh must exit 0 on a clean repo with starter-owned pages intact. '
                .'Output: '.implode("\n", $output),
        );
    }
}
