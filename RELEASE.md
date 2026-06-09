# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. Base and the starter are
free/public MIT projects on GitHub and Packagist. The current public release line
is `xuple/evolayer-base-starter v0.1.3` consuming `xuple/evolayer-base v0.1.1`
through the `^0.1` constraint. GitHub is the public publication source; the
self-hosted Forge remains an internal mirror.

Web IA is part of the release posture: `evodevops.com` teaches and markets the
family, `docs.evodevops.com/base` is the canonical Base documentation root, `/`
mounts the EvoLayer Base demo/install explainer, and the package's opt-in
marketing routes expose that same page at `/about`.

## Current public release

Current starter release: **0.1.3** (pre-1.0 developer preview). Future fixes use
new patch releases; do not move published tags.

## create-project flow (end users)

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app && npm install && npm run build && php artisan serve
```

`npm run build` is the default frontend quality gate and runs both the client
bundle and the SSR bundle.

`post-create-project-cmd` runs `key:generate`, creates the SQLite database,
`migrate --seed`, clears stale route caches, `wayfinder:generate`, and
`evolayer:ontology:compile`. Demo admin: `test@example.com` / `password`.

## Route identity migration notes

The starter treats `/home` (`evolayer.base.home`) as the canonical authenticated
entry point. Public `/` remains named `home` and remains the logout/public landing
route. When changing this contract in a downstream app, review:

- `config/fortify.php` `home`.
- Shell logo links, passkey success fallbacks, and any hard-coded `/dashboard`
  assumptions.
- Generated Wayfinder imports after route changes. Run `php artisan route:clear`
  before `php artisan wayfinder:generate --with-form --no-interaction`.

## How the package is resolved

The starter consumes the package from **Packagist** as a tagged release:

```jsonc
"require": { "xuple/evolayer-base": "^0.1" }
```

No custom `repositories` entry ships in the public starter — Composer resolves
`xuple/evolayer-base` straight from Packagist. `composer.lock` is **not
committed** (matches the `laravel/laravel` skeleton): each created project
resolves fresh and commits its own lock.

The rule changes after creation: a real client application should normally
commit its generated `composer.lock` so deployments resolve the same dependency
graph that was tested.

> **Pre-public staging (historical).** Before `xuple/evolayer-base` was
> published to Packagist, the starter resolved it from a private Forge `vcs`
> repository at `dev-main` (`ssh://git@<private-forge-host>/<owner>/evolayer-base.git`).
> That `repositories` block was removed when the starter moved to `^0.1`; it is
> retained here only as internal context.

**Local side-by-side package dev (optional):** to edit the package locally and
have this starter pick it up without pushing to the forge, add an *uncommitted*
path override:

```bash
composer config repositories.evolayer-base path ../evolayer-base   # do not commit
```

## Patch-release checklist

1. Full verification suite green: `composer test`, `composer validate --strict`, `php artisan evolayer:doctor --strict --no-ansi`, `npm run types:check`, `npm run build` (client + SSR), `composer lint:check`, `npm run lint:check`, `npm run format:check`.
2. The `^0.1` constraint resolves the published package from Packagist — `composer validate --strict` must stay clean.
3. A clean Packagist `composer create-project xuple/evolayer-base-starter <dir>` (no `--repository` / `--stability` flags) succeeds end to end.
4. **Live AI** — add provider keys to `.env`, then run the relevant smoke
   commands. ThreadStudio's runtime-approved providers are **Gemini** (default) and
   **OpenAI** — both are selectable as `AI_THREAD_STUDIO_PROVIDER` and pass
   structured streaming. **Anthropic** is diagnostic-eligible but blocked for
   ThreadStudio runtime / pending re-verification: its non-streaming
   `php artisan evolayer:ai:smoke-test anthropic` passes, but
   `php artisan evolayer:ai:stream-check anthropic` currently returns zero
   `TextDelta` events and an empty final payload, so it is not runtime-approved
   for ThreadStudio runtime selection. **NVIDIA / OpenCode / OpenRouter** remain
   router-backed diagnostic-eligible probe candidates, not runtime-approved
   ThreadStudio providers.
5. Move the relevant `CHANGELOG.md` `[Unreleased]` entries into the new patch
   version immediately before tagging.

The first-hour install path has been rehearsed end-to-end from a clean
directory: `composer create-project` resolved starter `v0.1.3` and base
`v0.1.1`, applied the `laravel/ai` patch, migrated/seeded, generated Wayfinder
and ontology, then `npm install`, `npm audit`, `npm run build`,
`evolayer:doctor --strict`, and `composer test` all passed.

## Distribution

`xuple/evolayer-base` and `xuple/evolayer-base-starter` are **public on GitHub
and published on Packagist**. The starter consumes the base package as `^0.1`
from Packagist. **GitHub is the public publication source**; the self-hosted
Forge remains an internal mirror (`origin`). See the package `RELEASE.md` for
the package-first release flow.

## CI access note

Public CI runs on push, pull request, and `workflow_dispatch`. It no longer
needs a repository-access secret: `composer install` resolves the base package
from Packagist, then the workflows run validation, audits, generated frontend
contract preparation, build, strict doctor, tests, lint, and format checks.

## Open decisions

The base and starter packages are tagged, public, Packagist-visible, and covered
by public CI. Remaining launch polish: the minimum `docs.evodevops.com/base`
page and announcement assets. The ThreadStudio provider roster is settled
(Gemini + OpenAI runtime-approved;
Anthropic diagnostic-eligible but blocked for ThreadStudio runtime / pending
re-verification; routers are router-backed diagnostic-eligible probe
candidates); what remains open there is Anthropic's structured-streaming
investigation. Remotes: `origin` is the internal Forge mirror; `github`
(`git@github.com:xuple/evolayer-base-starter.git`) is the public publication
source.
