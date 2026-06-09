# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. Base and the starter are
free/public MIT projects. `xuple/evolayer-base` is already public and published
on Packagist at `v0.1.0`; the starter consumes it as `^0.1` and is mid-launch —
public GitHub visibility + Packagist submission are the next step, and the
starter `v0.1.0` tag is still pending. GitHub is the public publication source;
the self-hosted Forge remains an internal mirror.

Web IA is part of the release posture: `evodevops.com` teaches and markets the
family, `docs.evodevops.com/base` is the canonical Base documentation root, `/`
mounts the EvoLayer Base demo/install explainer, and the package's opt-in
marketing routes expose that same page at `/about`.

## Provisional version

First release target: **0.1.0** (pre-1.0). Not yet tagged.

## create-project flow (end users)

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app && npm install && npm run build && php artisan serve
```

`npm run build` is the default frontend quality gate and runs both the client
bundle and the SSR bundle.

`post-create-project-cmd` runs `key:generate`, creates the SQLite database,
`migrate --seed`, `wayfinder:generate`, and `evolayer:ontology:compile`. Demo
admin: `test@example.com` / `password`.

## How the package is resolved

The starter consumes the package from **Packagist** as a tagged release:

```jsonc
"require": { "xuple/evolayer-base": "^0.1" }
```

No custom `repositories` entry ships in the public starter — Composer resolves
`xuple/evolayer-base` straight from Packagist. `composer.lock` is **not
committed** (matches the `laravel/laravel` skeleton): each created project
resolves fresh and commits its own lock.

> **Pre-launch staging (historical).** Before `xuple/evolayer-base` was
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

## Pre-release checklist (before tagging)

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
5. Move `CHANGELOG.md` `[Unreleased]` → `[0.1.0]`.

The first-hour install path has been rehearsed end-to-end from a clean
directory: `composer create-project` resolved the starter and package, applied
the `laravel/ai` patch, migrated/seeded, generated Wayfinder + ontology, then
`npm install`, `npm run build`, `evolayer:doctor --strict`, and `composer test`
all passed — including live Gemini + OpenAI structured-streaming compose.

## Distribution

`xuple/evolayer-base` is **public on GitHub and published on Packagist** at
`v0.1.0`; the starter consumes it as `^0.1` from Packagist. The starter is next:
make its GitHub repo public, submit it to Packagist, run the public
`create-project` rehearsal, then tag `v0.1.0`. **GitHub is the public
publication source**; the self-hosted Forge remains an internal mirror
(`origin`). See the package `RELEASE.md` for the full package-first bridge.

## CI access note

Now that `xuple/evolayer-base` is public on Packagist, CI no longer needs a
private package-access secret — `composer install` resolves the package from
Packagist. The GitHub workflows were `workflow_dispatch` only during the
private pre-release; **re-enable push/PR triggers after the final public
`composer create-project` rehearsal passes**. The temporary
`EVOLAYER_BASE_GITHUB_TOKEN` workaround is no longer needed.

## Open decisions

The base package is tagged and published (`xuple/evolayer-base v0.1.0`).
Remaining for the starter: public GitHub visibility + Packagist submission, the
public `create-project` rehearsal, the `v0.1.0` tag, the minimum
`docs.evodevops.com/base` page, and re-enabling GitHub CI — then announcement.
The ThreadStudio provider roster is settled (Gemini + OpenAI runtime-approved;
Anthropic diagnostic-eligible but blocked for ThreadStudio runtime / pending
re-verification; routers are router-backed diagnostic-eligible probe
candidates); what remains open there is Anthropic's structured-streaming
investigation. Remotes: `origin` is the internal Forge mirror; `github`
(`git@github.com:xuple/evolayer-base-starter.git`) is the public publication
source.
