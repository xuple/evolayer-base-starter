# Agents Guide — EvoLayer Base Starter

For AI coding agents (Claude Code, Codex, Aider, Cursor, etc.) and any automation operating on this repo. Humans should start with [`README.md`](README.md) and [`CONTRIBUTING.md`](CONTRIBUTING.md).

This file is the short, prescriptive version of those documents tuned for agent decision-making. When in doubt about an architectural rule, the routing matrix in [`CONTRIBUTING.md`](CONTRIBUTING.md) is the source of truth.

## What this repo is

`xuple/evolayer-base-starter` is the public `composer create-project` host application for **EvoLayer Base** — Xuple's AI / ontology / blocks substrate for Laravel + React + Inertia. Two repos work together:

| Repo | Role |
| --- | --- |
| [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) | The package. Owns examples, agents, blocks, ontology, `evolayer:*` artisan commands, and the `evolayer.base.*` config shape. Conservative — installs add no routes by default. |
| `xuple/evolayer-base-starter` (this repo) | Thin Laravel host shell. Owns the integration files the package can't publish, the kitchen-sink `.env.example` defaults, the `laravel/ai` patch wiring, host-side migrations, and starter CI. Kitchen-sink — every demo surface enabled out of the box. |

The starter is a thin fork of [`laravel/react-starter-kit`](https://github.com/laravel/react-starter-kit). Inherited scaffolding that doesn't fit the EvoLayer story (e.g. `resources/js/pages/welcome.tsx`) is kept intentionally where it's wired into upstream flows like the chisel auth-trim.

## Where does my change belong?

Decision rule before any edit:

1. **Is the file under `vendor/xuple/evolayer-base/`?** Never edit it from this starter. Fix it in the package repo, tag, then `composer update xuple/evolayer-base && composer evolayer:resync` here.
2. **Is the file a host integration file?** (See list in `README.md` → "What's pre-applied".) Starter-scoped.
3. **Is the file an example UI, agent, block, ontology entry, or `evolayer:*` artisan command?** Package-scoped — open the PR in `xuple/evolayer-base`.
4. **Cross-repo change** (e.g. new `EVOLAYER_BASE_*` flag): land the package PR first against a resolvable ref, then open the starter PR pointing at it.

Full matrix: [`CONTRIBUTING.md`](CONTRIBUTING.md) → "Where does my change belong?".

## Starter-owned, package-owned, exceptions

**Starter (edit here):**

- `app/Http/Middleware/HandleInertiaRequests.php` — shares `evolayer.base.{examples,features}`.
- `app/Models/User.php` — `HasRoles`, `PasskeyAuthenticatable`, `TwoFactorAuthenticatable`.
- `routes/web.php`, `routes/settings.php` — host route shell.
- `resources/js/app.tsx`, `resources/js/components/{app-sidebar,app-header}.tsx`, `resources/js/types/global.d.ts` — host wiring.
- `database/seeders/DatabaseSeeder.php`, `database/migrations/2026_05_24_*` — host-owned migrations (Spatie permission / activitylog / media / tags with ULID-compatible morph columns).
- `.env.example`, `composer.json` scripts (`setup`, `dev`, `evolayer:resync`, `post-create-project-cmd`, etc.).
- `patches/laravel-ai-structured-streaming.patch`, `patches.lock.json` (via `extra.patches` + `cweagans/composer-patches`).
- `.github/workflows/*`, `tests/Feature/**`, `tests/Unit/**`.

**Package (edit upstream, never here):**

- `vendor/xuple/evolayer-base/**` — including all `resources/js/pages/evolayer/**`, `resources/js/blocks/**`, `resources/js/hooks/use-evolayer-*`, the ontology, agents, and every `evolayer:*` artisan command.
- The `evolayer.base.*` config shape (`config/evolayer.php` keys + defaults are package-owned, values in `.env.example` are starter-owned).

**Exception — starter-owned landing pages:** `resources/js/pages/evolayer/about.tsx` and `resources/js/pages/evolayer/home.tsx` are starter-owned brand overrides of the package's defaults. `composer evolayer:resync` overwrites them; re-apply the overrides after a resync. All other `resources/js/pages/evolayer/**` files are package-owned.

## Hard rules

- **Do not commit `composer.lock`.** It's in `.gitignore`; both CI workflows fail if it appears. `composer create-project` must resolve `xuple/evolayer-base` fresh per install.
- **Do not edit anything under `vendor/`.** Patches go via `patches/` + `cweagans/composer-patches`; package fixes go upstream.
- **Do not introduce starter-local Dusk/Playwright/Cypress.** The starter ships PHPUnit Feature/HTTP tests only; browser/E2E coverage belongs in the package alongside the components it exercises.
- **Do not change `config/evolayer.php` defaults to `true`** to make tests easier. The package keeps defaults `false`; `.env.example` is the kitchen-sink switch.
- **Do not run `php artisan evolayer:install` in this starter.** That command is for adding Base to an existing Laravel app; its work is already pre-applied here. Use `composer evolayer:resync` to pull a newer package frontend instead.

## Frontend stub flow

The package publishes React stubs into the starter via `vendor:publish --tag=evolayer-base-frontend` so the starter clones and builds without an install step. These stubs are package-owned but live in this repo. When they regress format (Prettier's `prettier-plugin-tailwindcss` reorders Tailwind classes that the package doesn't pre-normalize), the mechanical fix is `npx prettier --write resources/ && eslint . --fix`. The kitchen-sink contract test does not depend on stub content; only the `EVOLAYER_BASE_*` flag shape.

## Feature-flag rules

Two prefixes, both static and `.env`-driven, read through `config/evolayer.php`, surfaced on the shared `evolayer.base.{examples,features}` Inertia prop:

- `EVOLAYER_BASE_EXAMPLE_*` — bundled demo surfaces. Disabling drops routes and hides the sidebar entry; the prop key stays present and its value flips to `false`.
- `EVOLAYER_BASE_FEATURE_*` — substrate capabilities (e.g. medialibrary-backed attachments). No routes or nav to drop; disabling just toggles the underlying capability and flips the prop value to `false`.

These are install-time switches, not per-user / rollout / A-B / billing flags. For those, reach for [Laravel Pennant](https://laravel.com/docs/pennant). Adding a new flag requires documenting category, default in both repos, gated surfaces, owner, and permanence — and the package PR for the config shape lands before the starter PR for the `.env.example` value.

## Wayfinder + ontology generation

`resources/js/{actions,routes,wayfinder}` and `resources/js/types/ontology.ts` are gitignored. Any frontend type-check or build on a fresh checkout must first run:

```bash
touch database/database.sqlite                              # if missing
php artisan migrate --seed --force
php artisan wayfinder:generate --with-form --no-interaction
php artisan evolayer:ontology:compile --no-erd --no-interaction
```

`composer setup` and `post-create-project-cmd` do this for end users; CI does it explicitly in the tests workflow. If you add a workflow, repeat that recipe before `npm run types:check` or `npm run build`.

## Patch policy

The only committed patch is `patches/laravel-ai-structured-streaming.patch`, applied automatically via `cweagans/composer-patches` (`extra.patches` in `composer.json`). It enables structured-output streaming until upstream `laravel/ai` ships the fix. See `patches/README.md` for the verification matrix and revisit conditions. Don't add new vendor patches without a similar dossier.

## Verification gauntlet

Run before opening a PR:

```bash
composer validate --strict
composer test                      # PHPUnit Feature + Unit
php artisan evolayer:doctor        # package's health check (informational; CI enforces strictness)
npm run types:check                # tsc --noEmit
composer lint:check                # Pint
npm run lint:check                 # ESLint
npm run format:check               # Prettier (resources/ only)
npm run build                      # Vite client + SSR
```

All eight gates green on HEAD before push. The starter CI runs the same set on `workflow_dispatch` (workflows are paused on push/PR until the package is public on Packagist — see RELEASE.md).

## Out of scope — do not invent

- New starter-local example routes, pages, AI agents, or blocks (those belong in the package).
- Provider-platform expansions (model sweeps, billing, cost estimation, stale-reprobe workflows). The package's `evolayer:ai:*` commands cover the minimum probe surface; keep starter docs to the one smoke command and link out.
- Sibling EvoLayer layers (Commerce / SaaS / RLS). Those ship as separate packages with their own starter repos.
- Hub / `evodevops.com` editorial work. That lives off-repo.
- Rewriting `welcome.tsx`. It's inherited Laravel-kit fallback; see the header comment in that file.

## Links

- [`README.md`](README.md) — full user-facing story.
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — full routing matrix + flag conventions + PR checklist.
- [`RELEASE.md`](RELEASE.md) — pre-release checklist, package resolution, public-launch swap.
- [`CHANGELOG.md`](CHANGELOG.md) — `[Unreleased]` covers everything between the EvoLayer pivot and the next tag.
- [`SECURITY.md`](SECURITY.md), [`SUPPORT.md`](SUPPORT.md) — community policies.
- [`patches/README.md`](patches/README.md) — vendor patch dossier.
