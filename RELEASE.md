# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. Base and the starter are
free/public MIT projects on GitHub and Packagist. The current public release line
is `xuple/evolayer-base-starter v0.1.19` consuming `xuple/evolayer-base` (exact-pinned while `0.x`, at `0.1.9`). GitHub is the public publication source; the
self-hosted Forge remains an internal mirror.

Web IA is part of the release posture: `evodevops.com` teaches and markets the
family, `evodevops.com/evolayer-base/docs` is the canonical Base documentation root, `/`
mounts the EvoLayer Base demo/install explainer, and the package's opt-in
marketing routes expose that same page at `/about`.

## Current public release

Current starter release: **0.1.19** (pre-1.0 developer preview). Future fixes use
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

The starter treats `/home` (host-owned route named `home`) as the canonical
authenticated entry point. Public `/` is named `welcome` and is the
logout/public landing route rendering the `evolayer/base` explainer. When
changing this contract in a downstream app, review:

- `config/fortify.php` `home`.
- Shell logo links, passkey success fallbacks, and any hard-coded `/dashboard`
  assumptions.
- Generated Wayfinder imports after route changes. Run `php artisan route:clear`
  before `php artisan wayfinder:generate --with-form --no-interaction`.

## How the package is resolved

The starter consumes the package from **Packagist** as a tagged release:

```jsonc
"require": { "xuple/evolayer-base": "0.1.9" }
```

No custom `repositories` entry ships in the public starter — Composer resolves
`xuple/evolayer-base` straight from Packagist, exact-pinned while `0.x`.
`composer.lock` **is committed**: the starter is a tested, reproducible
distribution and `composer create-project` installs the locked graph (Composer
honors a committed lock). Bump the framework deliberately via a release PR
rather than letting installs drift.

Created applications likewise commit their generated `composer.lock` so
deployments resolve the same dependency graph that was tested.

> **Pre-public staging (historical).** Before `xuple/evolayer-base` was
> published to Packagist, the starter resolved it from a private Forge `vcs`
> repository at `dev-main` (`ssh://git@<private-forge-host>/<owner>/evolayer-base.git`).
> That `repositories` block was removed when the starter moved to Packagist distribution; it is
> retained here only as internal context.

**Local side-by-side package dev (optional):** to edit the package locally and
have this starter pick it up without pushing to the forge, add an _uncommitted_
path override:

```bash
composer config repositories.evolayer-base path ../evolayer-base   # do not commit
```

## Patch-release checklist

1. Full verification suite green: `composer test`, `composer validate --strict`, `php artisan evolayer:doctor --strict --no-ansi`, `npm run types:check`, `npm run build` (client + SSR), `composer lint:check`, `npm run lint:check`, `npm run format:check`.
2. The exact-pinned dependency resolves the published package from Packagist — `composer validate --strict` must stay clean.
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
5. **Manual runtime smoke** — the following checks exercise browser behaviour
   that HTTP/string tests cannot verify. A human must perform them before
   tagging a release; no agent may claim these passed without actually running
   them in a browser.
    - Public `/` (route `welcome`) loads the EvoLayer Base explainer (`evolayer/base`).
    - Login/register path renders Fortify auth forms.

- Authenticated `/home` loads the Home launcher with command bar visible.
    - Home page command bar click opens the command palette.
    - Header search button/search icon opens the command palette.
    - `Ctrl`/`Cmd`+`K` opens the command palette via keyboard shortcut.
    - Settings navigation reaches Profile, Security, and Appearance tabs.
    - Logout redirects back to the public landing page.
    - `/dashboard` loads (retained scaffold route, not the primary post-auth destination).
6. **Manual social preview smoke** — run on a staging/production URL with real
   `APP_URL` / optional `SITE_URL` values. Verify at least Slack, Teams,
   Discord, LinkedIn Post Inspector, Meta Sharing Debugger, WhatsApp, and
   iMessage / Apple Messages when available. Check Google URL Inspection and
   favicon recrawl where applicable. Do not claim platform preview support from
   local HTTP tests alone; live unfurlers cache and crawl differently.

7. Move the relevant `CHANGELOG.md` `[Unreleased]` entries into the new patch
   version immediately before tagging.

The first-hour install path has been rehearsed end-to-end from a clean
directory before tagging: `composer create-project` must resolve the current
starter release and its exact-pinned base package, apply the `laravel/ai` patch,
migrate/seed, generate Wayfinder and ontology, and then pass `npm install`,
`npm audit`, `npm run build`, `evolayer:doctor --strict`, and `composer test`.

## Distribution

`xuple/evolayer-base` and `xuple/evolayer-base-starter` are **public on GitHub
and published on Packagist**. The published starter `v0.1.19` consumes the base package exact-pinned while `0.x` (at `0.1.9`)
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
by public CI. Remaining launch polish: the minimum `evodevops.com/evolayer-base/docs`
page and external announcement copy. (Starter-side social-preview metadata and
the default OG image now ship in-repo.) The ThreadStudio provider roster is settled
(Gemini + OpenAI runtime-approved;
Anthropic diagnostic-eligible but blocked for ThreadStudio runtime / pending
re-verification; routers are router-backed diagnostic-eligible probe
candidates); what remains open there is Anthropic's structured-streaming
investigation. Remotes: `origin` is the internal Forge mirror; `github`
(`git@github.com:xuple/evolayer-base-starter.git`) is the public publication
source.
