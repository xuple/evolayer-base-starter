# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. Base and the starter are
free/public MIT projects. The self-hosted Forge and private GitHub repositories
are pre-launch staging; the launch target is public GitHub plus Packagist.
Version/tag remains open.

Web IA is part of the release posture: `evodevops.com` teaches and markets the
family, `docs.evodevops.com/base` is the canonical Base documentation root, `/`
is the starter's demo/install landing page, and `/about` explains why the Base
layer exists.

## Provisional version

First release target: **0.1.0** (pre-1.0). Not yet tagged.

## create-project flow (end users)

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app && npm install && npm run build && php artisan serve
```

`post-create-project-cmd` runs `key:generate`, creates the SQLite database,
`migrate --seed`, `wayfinder:generate`, and `evolayer:ontology:compile`. Demo
admin: `test@example.com` / `password`.

## How the package is resolved

While the package is private/unpublished the starter resolves it from the
**Forge `vcs` repository** at `dev-main` — so `composer create-project` works
from any machine with Forge access, not just one with a sibling checkout:

```jsonc
"require":      { "xuple/evolayer-base": "dev-main" },
"repositories": [{ "type": "vcs",
                   "url": "ssh://git@forge.dev.home.arpa:222/xupleteam/evolayer-base.git" }]
```

`composer.lock` is **not committed** (matches the `laravel/laravel` skeleton): a
committed lock pinned the package to a machine-local source and broke
`create-project` elsewhere. Each created project resolves fresh and commits its
own lock. At public launch, `dev-main` becomes `^0.1` and the private Forge VCS
repository entry is removed so Composer resolves the package from Packagist.

**Local side-by-side package dev (optional):** to edit the package locally and
have this starter pick it up without pushing to the forge, add an *uncommitted*
path override:

```bash
composer config repositories.local path ../evodevops-base-pkg   # do not commit
```

## Pre-release checklist (before tagging)

1. `composer test`, `npm run types:check`, `npm run build` green.
2. `composer validate --strict` clean (the `dev-main` constraint is bound — no warning).
3. `php artisan evolayer:doctor` all-green.
4. `composer create-project xuple/evolayer-base-starter <dir> --repository='{"type":"vcs","url":"ssh://…/evolayer-base-starter.git"}' --stability=dev` succeeds end to end.
5. **Live AI** — add `GEMINI_API_KEY` / `ANTHROPIC_API_KEY` to `.env`, then run
   `php artisan evolayer:ai:stream-smoke gemini` and `... anthropic`. Blocked
   until keys are provided.
6. Move `CHANGELOG.md` `[Unreleased]` → `[0.1.0]`.

Verified from `/tmp/evolayer-test` using the Forge VCS repository argument:
`composer create-project` resolved the starter and package, applied the patch,
migrated/seeded, generated Wayfinder + ontology, then `npm install`,
`npm run build`, `php artisan evolayer:doctor`, and `composer test` all passed.

## Distribution (direction set)

Pre-launch staging is private: the self-hosted Forge is `origin`, and GitHub is
a mirror for collaboration. Public launch is GitHub + Packagist. At launch the
starter dependency becomes `"xuple/evolayer-base": "^0.1"` and the private Forge
`repositories` entry is removed. See the package `RELEASE.md` for the full
distribution model and push recipe.

## CI access note

Until `xuple/evolayer-base` is tagged and published on Packagist, CI must have
read access to that private package repository. Forge-based CI can use the
committed Forge VCS URL directly. GitHub-hosted CI needs an explicit
secret/deploy key/PAT with access to `xuple/evolayer-base`; otherwise
`composer install` will fail before tests run.

The GitHub workflows are intentionally `workflow_dispatch` only during this
private pre-release phase to avoid noisy failure emails on every push. Re-enable
push/PR triggers when the package is published on Packagist. A temporary
`EVOLAYER_BASE_GITHUB_TOKEN` secret can be used before then, but that is only a
pre-release workaround.

## Open decisions

Final version/tag, Packagist publication, GitHub CI re-enable, and live AI
verification (needs `GEMINI_API_KEY` / `ANTHROPIC_API_KEY`) are still pending.
Remotes are configured:
`origin` is `ssh://git@forge.dev.home.arpa:222/xupleteam/evolayer-base-starter.git`;
`github` is `git@github.com:xuple/evolayer-base-starter.git`.
