# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. **Nothing is tagged,
pushed, or published yet** — remotes, distribution, and version are open.

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

## Local path repository during development

While the package is unpublished, the starter resolves it from a sibling
directory via a `path` repository, with `xuple/evolayer-base: *@dev`:

```jsonc
"repositories": [{ "type": "path", "url": "../evodevops-base-pkg",
                   "options": { "symlink": false } }]
```

The directory name stays `evodevops-base-pkg` (filesystem ≠ package identity).
`composer validate --strict` warns about the unbound `*@dev` constraint — this is
**expected during development** and is swapped for a real version (e.g. `^0.1`)
once the package is tagged and reachable from a Composer repository.

## Pre-release checklist (before tagging)

1. `vendor/bin/phpunit`, `npm run types:check`, `npm run build` green.
2. `composer validate --strict` clean apart from the intentional `*@dev` warning.
3. `php artisan evolayer:doctor` all-green.
4. **Live AI** — add `GEMINI_API_KEY` / `ANTHROPIC_API_KEY` to `.env`, then run
   `php artisan evolayer:ai:stream-smoke gemini` and `... anthropic`. Blocked
   until keys are provided.
5. Move `CHANGELOG.md` `[Unreleased]` → `[0.1.0]`.

## Open decisions

Remotes, Packagist vs private distribution, final version/tag, and live AI
verification (needs API keys) — all deferred. See the package `RELEASE.md`.
