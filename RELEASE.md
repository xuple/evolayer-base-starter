# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. The repository is pushed to
the self-hosted forge and mirrored to GitHub; version/tag and public
distribution remain open.

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

While the package is private/unpublished the starter resolves it from the **forge
`vcs` repository** at `dev-main` — so `composer create-project` works from any
machine with forge access, not just one with a sibling checkout:

```jsonc
"require":      { "xuple/evolayer-base": "dev-main" },
"repositories": [{ "type": "vcs",
                   "url": "ssh://git@forge.dev.home.arpa:222/xupleteam/evolayer-base.git" }]
```

`composer.lock` is **not committed** (matches the `laravel/laravel` skeleton): a
committed lock pinned the package to a machine-local source and broke
`create-project` elsewhere. Each created project resolves fresh and commits its
own lock. After the package is tagged, `dev-main` becomes `^0.1`.

**Local side-by-side package dev (optional):** to edit the package locally and
have this starter pick it up without pushing to the forge, add an *uncommitted*
path override:

```bash
composer config repositories.local path ../evodevops-base-pkg   # do not commit
```

## Pre-release checklist (before tagging)

1. `vendor/bin/phpunit`, `npm run types:check`, `npm run build` green.
2. `composer validate --strict` clean (the `dev-main` constraint is bound — no warning).
3. `php artisan evolayer:doctor` all-green.
4. `composer create-project xuple/evolayer-base-starter <dir> --repository='{"type":"vcs","url":"ssh://…/evolayer-base-starter.git"}' --stability=dev` succeeds end to end (verified).
4. **Live AI** — add `GEMINI_API_KEY` / `ANTHROPIC_API_KEY` to `.env`, then run
   `php artisan evolayer:ai:stream-smoke gemini` and `... anthropic`. Blocked
   until keys are provided.
5. Move `CHANGELOG.md` `[Unreleased]` → `[0.1.0]`.

## Distribution (direction set)

Private-first: a self-hosted git server is the primary `origin`, with a private
GitHub repo (a few collaborators) as a likely mirror — **not** public Packagist
while private. At publish time the dev `path` repo is replaced by a `vcs`
repository pointing at the package's private git URL, and the dependency becomes
`"xuple/evolayer-base": "^0.1"`. See the package `RELEASE.md` for the full
distribution model and the push recipe.

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

Final version/tag and live AI verification (needs `GEMINI_API_KEY` /
`ANTHROPIC_API_KEY`) are still pending. Remotes are configured:
`origin` is `ssh://git@forge.dev.home.arpa:222/xupleteam/evolayer-base-starter.git`;
`github` is `git@github.com:xuple/evolayer-base-starter.git`.
