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
use Illuminate\Contracts\Console\Kernel;

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

    // Exactly one blank line on each side of the block — trimming the rest's
    // leading newlines so the insertion is boost:update-idempotent (no double
    // blank lines for boost to normalize → no spurious doc churn). [EDV-10]
    return rtrim(substr($content, 0, $headingEnd))
        .PHP_EOL.PHP_EOL
        .$block
        .PHP_EOL.PHP_EOL
        .ltrim(substr($content, $headingEnd));
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

function evolayer_install_profile_guidance(string $profile = 'demo'): string
{
    if ($profile === 'application') {
        return <<<'MD'
<!-- evolayer-application-profile:start -->
> **Operational profile: application.** Public registration, known demo credentials, and bundled example surfaces are disabled. Regenerate Wayfinder and ontology contracts, run the Starter verification gates, and deliberately update the exact Base pin when adopting a reviewed release.
<!-- evolayer-application-profile:end -->
MD;
    }

    return <<<'MD'
<!-- evolayer-application-profile:start -->
> **Operational profile: demo.** Public registration and bundled examples are enabled for local exploration. The seeded test@example.com / password account is demonstration-only. Apply the application profile before treating this repository as an application deployment.
<!-- evolayer-application-profile:end -->
MD;
}

/**
 * A fresh, app-appropriate README for generated apps. The starter's own README is
 * `export-ignore`d from the Composer dist, so `create-project` apps ship without
 * one. Rather than a thin stub, port the genuinely useful operational content
 * (surfaces, flags, AI providers, resync, tooling) into an *app-voiced* README —
 * dropping the starter's marketing/distribution chrome (badges, create-project
 * Quick Start, developer-preview) that is wrong inside a generated app. [EDV-11]
 */
function evolayer_install_generated_readme(string $appRoot, string $suggestedPackageName): string
{
    $app = basename($appRoot);
    $profileGuidance = evolayer_install_profile_guidance();

    return <<<MD
# {$app}

> **Your application** — created from [`xuple/evolayer-base-starter`](https://github.com/xuple/evolayer-base-starter), **not the public starter.** See `AGENTS.md` / `CLAUDE.md` for agent guidance and the starter→app boundary.

A Laravel · React · Inertia application built on **EvoLayer Base** — the AI / ontology / blocks substrate for the `laravel/ai` SDK.

{$profileGuidance}

## Getting started

The `create-project` hook already generated an app key, an SQLite database, ran migrations + seeders, and compiled the Wayfinder/ontology caches. To run it locally:

```bash
composer install
npm install
composer dev          # server + queue + logs + Vite together
```

## How the pieces fit

- **You own** your routes, models, config, branding, and any surface you eject.
- **EvoLayer Base manages** the AI runtime, ontology, `evolayer:*` commands, and example surfaces — kept current via `composer evolayer:resync` so your tree stays clean. **Never edit `vendor/`.**
- **Ejecting empowers you:** `php artisan evolayer:eject <surface>` hands you full ownership of a managed surface's code.

| Layer | Owns |
| --- | --- |
| `xuple/evolayer-base` (package) | Examples, blocks, agents, ontology, `evolayer:*` commands, the `evolayer.base.*` config shape |
| **this app** | Your routes, pages, models, config, branding, migrations, deployment |

## Example surfaces & feature flags

Every bundled surface toggles independently via an `EVOLAYER_BASE_EXAMPLE_*` flag in `.env` (substrate capabilities use `EVOLAYER_BASE_FEATURE_*`). Set a flag to `false` to drop that surface's routes and sidebar entry.

| Flag | What it adds |
| --- | --- |
| `EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES` | Public `/about` explainer alias (authenticated `/home` is host-owned, always on) |
| `EVOLAYER_BASE_EXAMPLE_THREAD_STUDIO` | ThreadStudio — streaming AI compose with structured output |
| `EVOLAYER_BASE_EXAMPLE_PRD_STUDIO` | PRD Studio — turn notes into scoped requirements |
| `EVOLAYER_BASE_EXAMPLE_ADMIN_INBOX` | Admin inbox for contact-form submissions |
| `EVOLAYER_BASE_EXAMPLE_CONTACT_AI` | AI-assisted contact form (triage, auto-tagging) |
| `EVOLAYER_BASE_EXAMPLE_VOICE_INPUT` | Voice-input block |
| `EVOLAYER_BASE_EXAMPLE_AI_TEXT_FIELD` | `<AiTextField>` — inline streaming suggestions |
| `EVOLAYER_BASE_FEATURE_CONTACT_ATTACHMENTS` | Contact-form attachments (medialibrary) |

## AI providers

EvoLayer Base streams structured output via the `laravel/ai` SDK (defaults to **Gemini**; also supports OpenAI, Anthropic, DeepSeek, Groq, xAI, Mistral, Ollama). Set your provider key in `.env` (`GEMINI_API_KEY`, `OPENAI_API_KEY`, …) then verify end to end:

```bash
php artisan evolayer:ai:stream-check gemini
```

If streaming misbehaves, run `php artisan evolayer:doctor` — it health-checks the install (including the committed `laravel/ai` structured-streaming patch).

## Updating the framework

```bash
composer update xuple/evolayer-base
composer evolayer:resync
```

`evolayer:resync` is manifest-safe — it updates pristine stubs, keeps your edits, and skips ejected surfaces (`--force` to overwrite local edits, `--dry-run` to preview). Fix package internals [upstream](https://github.com/xuple/evolayer-base), then resync here — never by editing `vendor/`.

## Tooling & verification

```bash
composer dev          # server + queue + logs + Vite
php artisan evolayer:doctor
composer test         # Pest 4
composer lint         # Pint
npm run types:check   # tsc --noEmit
npm run build         # Vite client + SSR
```

This app is pre-wired for AI coding agents (Claude Code, Codex, OpenCode, Cursor) via [Laravel Boost](https://laravel.com/docs/boost) — see `AGENTS.md` / `CLAUDE.md`. Boost is a `require-dev` dependency, so the MCP layer needs dev dependencies installed.

## Notes

- Commit `composer.lock` for reproducible installs (team / CI / production).
- Optional: claim your own Composer package name with `composer config name {$suggestedPackageName}`.
MD;
}

/**
 * App-appropriate CONTRIBUTING. The starter's own CONTRIBUTING.md *is* shipped in
 * the dist (not export-ignored), so generated apps inherit guidance about
 * contributing to the public starter — wrong for an app. Replace it. [EDV-11]
 */
function evolayer_install_generated_contributing(string $appRoot): string
{
    $app = basename($appRoot);

    return <<<MD
# Contributing to {$app}

This is a private application generated from `xuple/evolayer-base-starter`. These
notes are for people working on **this app** — not on the public starter.

## Boundaries

- **Don't edit `vendor/`.** Framework features, ontology, and `evolayer:*` commands
  live in the `xuple/evolayer-base` package — change them upstream, then pull with
  `php artisan evolayer:resync`.
- App-owned code (your pages, routes, config, marketing) is yours to change freely.

## Before you push

```bash
composer test
npm run types:check
composer lint:check && npm run lint:check
npm run build
```

## Agent guidance

See `AGENTS.md` / `CLAUDE.md` for the full starter→app boundary and conventions.
MD;
}

function evolayer_install_apply_generated_identity(string $appRoot, string $suggestedPackageName): void
{
    $agentBlock = evolayer_install_agent_identity_block($suggestedPackageName);
    $profileBlock = evolayer_install_profile_guidance();

    foreach (['AGENTS.md', 'CLAUDE.md'] as $agentDoc) {
        $path = $appRoot.'/'.$agentDoc;

        if (! is_file($path)) {
            continue;
        }

        $content = (string) file_get_contents($path);

        $content = evolayer_install_marked_block(
            $content,
            '<!-- evolayer-generated-app-identity:start -->',
            '<!-- evolayer-generated-app-identity:end -->',
            $agentBlock,
            fn (string $existing, string $block): string => evolayer_install_insert_after_heading($existing, $block),
        );
        $content = evolayer_install_marked_block(
            $content,
            '<!-- evolayer-application-profile:start -->',
            '<!-- evolayer-application-profile:end -->',
            $profileBlock,
            fn (string $existing, string $block): string => evolayer_install_insert_after_heading($existing, $block),
        );

        file_put_contents($path, $content);
    }

    $readmePath = $appRoot.'/README.md';

    if (is_file($readmePath)) {
        $content = (string) file_get_contents($readmePath);

        $content = evolayer_install_marked_block(
            $content,
            '<!-- evolayer-generated-app-readme:start -->',
            '<!-- evolayer-generated-app-readme:end -->',
            evolayer_install_readme_identity_note($suggestedPackageName),
            fn (string $existing, string $block): string => $block.PHP_EOL.PHP_EOL.ltrim($existing),
        );
        $content = evolayer_install_marked_block(
            $content,
            '<!-- evolayer-application-profile:start -->',
            '<!-- evolayer-application-profile:end -->',
            $profileBlock,
            fn (string $existing, string $block): string => evolayer_install_insert_after_heading($existing, $block),
        );

        file_put_contents($readmePath, $content);
    } else {
        // No README shipped (export-ignored from the dist) → write a fresh one so
        // the generated app is never README-less.
        file_put_contents(
            $readmePath,
            evolayer_install_generated_readme($appRoot, $suggestedPackageName).PHP_EOL,
        );
    }

    // Replace the shipped starter CONTRIBUTING (about contributing to the public
    // starter) with app-appropriate guidance. Deterministic content → idempotent.
    file_put_contents(
        $appRoot.'/CONTRIBUTING.md',
        evolayer_install_generated_contributing($appRoot).PHP_EOL,
    );
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
        'schema_version' => 2,
        'kind' => 'generated-application',
        'profile' => 'demo',
        'overrides' => [
            'examples' => [],
            'features' => [],
        ],
        'applied_with' => array_filter([
            'base' => $frameworkVersion,
            'starter' => $rootPackage['pretty_version'] ?? null,
        ], fn (?string $version): bool => $version !== null),
        'distribution' => $rootPackage['name'] ?? 'xuple/evolayer-base-starter',
        'distribution_version' => $rootPackage['pretty_version'] ?? null,
        'framework' => $framework,
        'framework_version' => $frameworkVersion,
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

    $requestedProfile = getenv('EVOLAYER_BASE_INSTALL_PROFILE');
    $requestedProfile = is_string($requestedProfile) && $requestedProfile !== ''
        ? $requestedProfile
        : 'demo';

    if (! in_array($requestedProfile, ['demo', 'application'], true)) {
        throw new RuntimeException('EVOLAYER_BASE_INSTALL_PROFILE must be demo or application.');
    }

    $root = InstalledVersions::getRootPackage();
    $meta = evolayer_finalize_generated_app_identity($appRoot, $root, $frameworkVersion);

    if ($requestedProfile === 'application') {
        $app = require $appRoot.'/bootstrap/app.php';
        $kernel = $app->make(Kernel::class);
        $kernel->bootstrap();
        $exitCode = $kernel->call('evolayer:profile', [
            'profile' => 'application',
            '--no-interaction' => true,
        ]);

        if ($exitCode !== 0) {
            throw new RuntimeException('Unable to apply application posture before seeding.');
        }
    }

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
