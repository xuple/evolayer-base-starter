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

require __DIR__.'/../vendor/autoload.php';

use Composer\InstalledVersions;

$framework = 'xuple/evolayer-base';

$frameworkVersion = InstalledVersions::isInstalled($framework)
    ? InstalledVersions::getPrettyVersion($framework)
    : null;

$root = InstalledVersions::getRootPackage();

$meta = [
    'distribution' => $root['name'] ?? 'xuple/evolayer-base-starter',
    'distribution_version' => $root['pretty_version'] ?? null,
    'framework' => $framework,
    'framework_version' => $frameworkVersion,
    'mode' => 'application',
    'lock_policy' => 'commit',
    'created_at' => date('c'),
];

@mkdir(__DIR__.'/../.evolayer', 0755, true);

file_put_contents(
    __DIR__.'/../.evolayer/project.json',
    json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
);

$version = $frameworkVersion ?? 'unknown';

fwrite(STDOUT, <<<TXT

  EvoLayer Base installed as an application project (framework {$version}).

  • Commit composer.lock — it pins the tested dependency graph for reproducible
    installs across your team, CI, and production.
  • Framework-managed surfaces (AI runtime, ontology, examples) update through
    the xuple/evolayer-base package, not by editing files in place.
  • App-owned and ejected files are never overwritten by `php artisan evolayer:resync`.
  • Verify your install:  php artisan evolayer:doctor

TXT);
