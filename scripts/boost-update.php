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
 */
$root = dirname(__DIR__);

$command = [PHP_BINARY, 'artisan', 'boost:update', '--ansi'];

if (! is_dir($root.'/node_modules')) {
    $command[] = '--ignore-skills';
}

$escaped = implode(' ', array_map('escapeshellarg', $command));

passthru($escaped, $exitCode);

exit($exitCode);
