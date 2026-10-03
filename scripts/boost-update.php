<?php

/**
 * EvoLayer Base Starter — guarded `boost:update` wrapper.
 *
 * `boost:update` derives part of its applicable skill set from installed
 * JavaScript packages. Running it while `node_modules/` is absent makes those
 * packages undetectable, and Boost prunes the corresponding skills — deleting
 * `.claude/skills/**` and `.agents/skills/**` entries and dropping them from
 * `boost.json`, while the guidelines block in `AGENTS.md` / `CLAUDE.md`
 * continues to instruct agents to activate them.
 *
 * This is reachable on a normal workflow: `post-create-project-cmd` does not
 * install npm dependencies, so a fresh generated application that runs
 * `composer update` before `npm install` silently loses JS-detected skills
 * (observed downstream: `inertia-react-development`).
 *
 * When JS dependency detection is impossible, update guidelines but leave the
 * skills directory alone rather than pruning on incomplete information.
 *
 * Boost 2.10+ also writes the running PHP version into the generated block, and
 * the bundled skills differ between Boost releases. Regenerated output is only
 * canonical when produced on the PHP minor that composer.json declares as the
 * floor (currently 8.4), so on any other runtime this wrapper skips
 * regeneration and says so, instead of rewriting AGENTS.md / CLAUDE.md and the
 * skills with a misstated version. Set BOOST_UPDATE_ANY_PHP=1 to override.
 */
$root = dirname(__DIR__);

$composer = json_decode((string) file_get_contents($root.'/composer.json'), true);
$floor = null;

if (preg_match('/(\d+)\.(\d+)/', (string) ($composer['require']['php'] ?? ''), $matches) === 1) {
    $floor = $matches[1].'.'.$matches[2];
}

$running = PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;

if ($floor !== null && $running !== $floor && getenv('BOOST_UPDATE_ANY_PHP') !== '1') {
    fwrite(STDOUT, sprintf(
        "  - boost:update skipped: running PHP %s, but the generated guidelines and skills are canonical on PHP %s (composer.json floor).\n".
        "    Regenerate on PHP %s, or set BOOST_UPDATE_ANY_PHP=1 to override.\n",
        $running,
        $floor,
        $floor,
    ));

    exit(0);
}

$command = [PHP_BINARY, 'artisan', 'boost:update', '--ansi'];

if (! is_dir($root.'/node_modules')) {
    $command[] = '--ignore-skills';
}

$escaped = implode(' ', array_map('escapeshellarg', $command));

passthru($escaped, $exitCode);

exit($exitCode);
