<?php

/**
 * EvoLayer Base — install finalizer.
 *
 * Runs from `post-create-project-cmd` in a freshly created application. It
 * writes app-side install metadata (.evolayer/project.json) and prints the
 * install contract so the boundary between framework-managed and app-owned
 * surfaces is explicit at the moment of install — part of the public promise,
 * not buried in docs.
 *
 * This script intentionally only runs on `composer create-project`, never on a
 * plain `composer install`, so the template repo itself never generates the
 * metadata file.
 */

require_once __DIR__.'/../vendor/autoload.php';

use Composer\InstalledVersions;

function evolayer_install_slug(string $value): string
{
    $slug = preg_replace('/[^A-Za-z0-9]+/', '-', $value) ?? '';
    $slug = strtolower(trim($slug, '-'));

    return $slug !== '' ? $slug : 'application';
}

function evolayer_install_suggested_package_name(string $appRoot): string
{
    return 'app/'.evolayer_install_slug(basename($appRoot));
}

function evolayer_install_marked_block(
    string $content,
    string $startMarker,
    string $endMarker,
    string $block,
    callable $insert,
): string {
    $pattern = '/'.preg_quote($startMarker, '/').'.*?'.preg_quote($endMarker, '/').'\R*/s';

    if (preg_match($pattern, $content) === 1) {
        return preg_replace($pattern, $block.PHP_EOL.PHP_EOL, $content, 1) ?? $content;
    }

    return $insert($content, $block);
}

function evolayer_install_insert_after_heading(string $content, string $block): string
{
    if (preg_match('/^# .+$/m', $content, $matches, PREG_OFFSET_CAPTURE) !== 1) {
        return $block.PHP_EOL.PHP_EOL.ltrim($content);
    }

    $headingEnd = $matches[0][1] + strlen($matches[0][0]);

    return substr($content, 0, $headingEnd)
        .PHP_EOL.PHP_EOL
        .$block
        .PHP_EOL
        .substr($content, $headingEnd);
}

function evolayer_install_agent_identity_block(string $suggestedPackageName): string
{
    return <<<MD
<!-- evolayer-generated-app-identity:start -->
> **Generated application identity**
>
> This codebase was created from `xuple/evolayer-base-starter`; you are working in the generated application, not maintaining the public starter distribution. Starter-only distribution rules are guidance, not binding product policy for this app: you may own marketing surfaces, add browser/E2E tests, and choose app-specific docs when appropriate.
>
> The EvoLayer package boundary still applies. Framework features, ontology commands, package-managed stubs, and `evolayer:*` behavior belong upstream in `xuple/evolayer-base` unless this app has explicitly ejected or owns the surface. Commit `composer.lock` for reproducible app deploys, but it is not a public starter distribution artifact.
>
> Optional Composer identity: keep the inherited package name for a valid lock, or deliberately run `composer config name {$suggestedPackageName}` followed by `composer update --lock` when you are ready to rename the app.
<!-- evolayer-generated-app-identity:end -->
MD;
}

function evolayer_install_readme_identity_note(string $suggestedPackageName): string
{
    return <<<MD
<!-- evolayer-generated-app-readme:start -->
> **Generated app identity:** This application was created from `xuple/evolayer-base-starter`. Keep the inherited Composer package name for the fastest valid install, or deliberately run `composer config name {$suggestedPackageName}` followed by `composer update --lock` when you are ready to rename the private app.
<!-- evolayer-generated-app-readme:end -->
MD;
}

function evolayer_install_apply_generated_identity(string $appRoot, string $suggestedPackageName): void
{
    $agentBlock = evolayer_install_agent_identity_block($suggestedPackageName);

    foreach (['AGENTS.md', 'CLAUDE.md'] as $agentDoc) {
        $path = $appRoot.'/'.$agentDoc;

        if (! is_file($path)) {
            continue;
        }

        $content = (string) file_get_contents($path);

        file_put_contents(
            $path,
            evolayer_install_marked_block(
                $content,
                '<!-- evolayer-generated-app-identity:start -->',
                '<!-- evolayer-generated-app-identity:end -->',
                $agentBlock,
                fn (string $existing, string $block): string => evolayer_install_insert_after_heading($existing, $block),
            ),
        );
    }

    $readmePath = $appRoot.'/README.md';

    if (is_file($readmePath)) {
        $content = (string) file_get_contents($readmePath);

        file_put_contents(
            $readmePath,
            evolayer_install_marked_block(
                $content,
                '<!-- evolayer-generated-app-readme:start -->',
                '<!-- evolayer-generated-app-readme:end -->',
                evolayer_install_readme_identity_note($suggestedPackageName),
                fn (string $existing, string $block): string => $block.PHP_EOL.PHP_EOL.ltrim($existing),
            ),
        );
    }
}

/**
 * @param  array{name?: string, pretty_version?: string|null}  $rootPackage
 * @return array<string, mixed>
 */
function evolayer_finalize_generated_app_identity(
    string $appRoot,
    array $rootPackage = [],
    ?string $frameworkVersion = null,
): array {
    $framework = 'xuple/evolayer-base';
    $suggestedPackageName = evolayer_install_suggested_package_name($appRoot);

    $meta = [
        'distribution' => $rootPackage['name'] ?? 'xuple/evolayer-base-starter',
        'distribution_version' => $rootPackage['pretty_version'] ?? null,
        'framework' => $framework,
        'framework_version' => $frameworkVersion,
        'mode' => 'application',
        'lock_policy' => 'commit',
        'generated_app' => [
            'identity_finalized' => true,
            'install_directory' => basename($appRoot),
            'source_starter' => 'xuple/evolayer-base-starter',
            'suggested_package_name' => $suggestedPackageName,
        ],
        'created_at' => date('c'),
    ];

    @mkdir($appRoot.'/.evolayer', 0755, true);

    file_put_contents(
        $appRoot.'/.evolayer/project.json',
        json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
    );

    evolayer_install_apply_generated_identity($appRoot, $suggestedPackageName);

    return $meta;
}

function evolayer_finalize_install(string $appRoot): void
{
    $framework = 'xuple/evolayer-base';

    $frameworkVersion = InstalledVersions::isInstalled($framework)
        ? InstalledVersions::getPrettyVersion($framework)
        : null;

    $root = InstalledVersions::getRootPackage();
    $meta = evolayer_finalize_generated_app_identity($appRoot, $root, $frameworkVersion);
    $version = $frameworkVersion ?? 'unknown';
    $suggestedPackageName = $meta['generated_app']['suggested_package_name'];

    fwrite(STDOUT, <<<TXT

  EvoLayer Base installed as an application project (framework {$version}).

  • Commit composer.lock — it pins the tested dependency graph for reproducible
    installs across your team, CI, and production.
  • Framework-managed surfaces (AI runtime, ontology, examples) update through
    the xuple/evolayer-base package, not by editing files in place.
  • App-owned and ejected files are never overwritten by `php artisan evolayer:resync`.
  • Optional Composer app identity: composer config name {$suggestedPackageName}
  • Verify your install:  php artisan evolayer:doctor

TXT);
}

if (realpath((string) ($_SERVER['SCRIPT_FILENAME'] ?? '')) === realpath(__FILE__)) {
    // Only brand during `composer create-project`, which passes --create-project.
    // A bare manual run is refused so the starter source can never self-brand its
    // own README/AGENTS/CLAUDE by accident.
    if (in_array('--create-project', $argv ?? [], true)) {
        evolayer_finalize_install(dirname(__DIR__));
    } else {
        fwrite(
            STDERR,
            "evolayer-finalize-install: skipped — this runs only during composer create-project (pass --create-project to force).\n"
        );
    }
}
