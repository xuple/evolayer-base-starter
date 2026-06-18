<?php

require_once dirname(__DIR__, 2).'/scripts/evolayer-finalize-install.php';

function generatedAppIdentityFixture(string $directoryName): string
{
    $parent = sys_get_temp_dir().'/evolayer-generated-app-identity-'.bin2hex(random_bytes(4));
    $root = $parent.'/'.$directoryName;

    mkdir($root, 0755, true);

    file_put_contents(
        $root.'/composer.json',
        json_encode(['name' => 'xuple/evolayer-base-starter'], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL,
    );

    $agentDoc = <<<'MD'
# Agents Guide - EvoLayer Base Starter

Starter-maintainer guidance remains here.
MD;

    file_put_contents($root.'/AGENTS.md', $agentDoc.PHP_EOL);
    file_put_contents($root.'/CLAUDE.md', $agentDoc.PHP_EOL);
    file_put_contents($root.'/README.md', "# EvoLayer Base\n\nStarter README.\n");

    return $root;
}

function removeGeneratedAppIdentityFixture(string $root): void
{
    if (! is_dir($root)) {
        return;
    }

    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST,
    );

    foreach ($iterator as $item) {
        $item->isDir() ? rmdir($item->getPathname()) : unlink($item->getPathname());
    }

    rmdir($root);

    $parent = dirname($root);

    if (
        str_starts_with(basename($parent), 'evolayer-generated-app-identity-')
        && is_dir($parent)
        && count(scandir($parent) ?: []) === 2
    ) {
        rmdir($parent);
    }
}

test('post-create finalizer injects generated app identity without renaming composer package', function () {
    $root = generatedAppIdentityFixture('Client Portal 2026');

    try {
        $meta = evolayer_finalize_generated_app_identity(
            $root,
            ['name' => 'xuple/evolayer-base-starter', 'pretty_version' => 'dev-main'],
            'v0.1.5',
        );

        $agents = (string) file_get_contents($root.'/AGENTS.md');
        $claude = (string) file_get_contents($root.'/CLAUDE.md');
        $readme = (string) file_get_contents($root.'/README.md');
        $composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
        $project = json_decode((string) file_get_contents($root.'/.evolayer/project.json'), true);

        expect($meta['generated_app']['suggested_package_name'])->toBe('app/client-portal-2026')
            ->and($project['generated_app']['suggested_package_name'])->toBe('app/client-portal-2026')
            ->and($project['generated_app']['identity_finalized'])->toBeTrue()
            ->and($composer['name'])->toBe('xuple/evolayer-base-starter')
            ->and($agents)->toBe($claude)
            ->and($agents)->toContain('Generated application identity')
            ->and($agents)->toContain('not maintaining the public starter distribution')
            ->and($agents)->toContain('you may own marketing surfaces, add browser/E2E tests')
            ->and($agents)->toContain('composer config name app/client-portal-2026')
            ->and($readme)->toContain('Generated app identity')
            ->and($readme)->toContain('composer config name app/client-portal-2026');
    } finally {
        removeGeneratedAppIdentityFixture($root);
    }
});

test('post-create identity injection is idempotent', function () {
    $root = generatedAppIdentityFixture('My App');

    try {
        evolayer_finalize_generated_app_identity($root);
        evolayer_finalize_generated_app_identity($root);

        $agents = (string) file_get_contents($root.'/AGENTS.md');
        $readme = (string) file_get_contents($root.'/README.md');

        expect(substr_count($agents, '<!-- evolayer-generated-app-identity:start -->'))->toBe(1)
            ->and(substr_count($readme, '<!-- evolayer-generated-app-readme:start -->'))->toBe(1);
    } finally {
        removeGeneratedAppIdentityFixture($root);
    }
});

test('finalizer auto-run only brands during create-project (footgun guard)', function () {
    $repoRoot = dirname(__DIR__, 2);
    $composer = (string) file_get_contents($repoRoot.'/composer.json');
    $script = (string) file_get_contents($repoRoot.'/scripts/evolayer-finalize-install.php');

    // The create-project hook passes an explicit flag, and the script only
    // auto-brands when that flag is present — so a bare manual run can never
    // self-brand the starter source's own README/AGENTS/CLAUDE.
    expect($composer)->toContain('scripts/evolayer-finalize-install.php --create-project')
        ->and($script)->toContain("in_array('--create-project'");
});
