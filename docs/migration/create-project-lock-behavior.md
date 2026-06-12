# WS-0 — Does `composer create-project` honor a committed `composer.lock`?

**Status:** Resolved (2026-06-12). **Answer: yes.** This is the empirical
prerequisite for the framework-distribution lock policy (commit the lock in the
template so installs are reproducible). The whole policy hinges on `create-project`
installing the *locked* graph rather than re-resolving it, so it was verified
rather than assumed.

## Environment

- Composer **2.8.9** (2025-05-13), PHP 8.4.

## Test

A scratch package committed a `composer.lock` pinning a dependency to an **old**
version under a **loose** constraint, then was installed via `create-project`:

- `require`: `psr/log: ^1.0`
- lock pinned (via `composer update --prefer-lowest`): `psr/log 1.0.0`
- a *fresh* resolution of `^1.0` would pick `1.1.4` (latest in range)
- the package was committed + tagged, then installed from a local VCS source:

```bash
composer create-project --repository='{"type":"vcs","url":"<src>"}' acme/locktest <out> "^1.0"
```

## Result

The created app received **`psr/log 1.0.0`** — the *locked* version — in both
its generated `composer.lock` and on disk. Had `create-project` re-resolved, it
would have installed `1.1.4`. → **`create-project` honors a committed lock.**

## Consequences for the starter

1. **Install front door is the simple path.** Commit the lock; the normal
   `composer create-project xuple/evolayer-base-starter <app>` installs the
   tested graph. No `--no-install` two-step and no `evolayer new` installer
   wrapper are required.
2. **The real residual risk is dist packaging, not `create-project`.** The lock
   only reaches the Packagist dist zipball if it is **committed** *and* **not
   `export-ignore`d** in `.gitattributes`. `.gitattributes` is clean today
   (no `composer.lock export-ignore`); both CI workflows now assert this so a
   future `export-ignore` can't silently strip the lock and reintroduce drift.
3. **Freshness is release-managed.** `xuple/evolayer-base` is exact-pinned while
   `0.x`; bump it through a deliberate release PR rather than letting installs
   drift.

## Re-verify when

- Bumping the pinned Composer version in CI (behavior is version-sensitive).
- Changing how the starter is distributed (e.g. a non-Packagist dist path).
