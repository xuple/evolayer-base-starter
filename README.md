# EvoLayer Base Starter

A kitchen-sink Laravel + React + Inertia starter for exploring **EvoLayer Base** — Xuple's AI, ontology, and blocks substrate for Laravel apps, built on the [`laravel/ai`](https://github.com/laravel/ai) SDK.

- **Start here** when you want a full demo app with EvoLayer Base already wired in, every example surface visible.
- **Use the [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) package directly** when adding Base to an existing Laravel app.

> **Pre-release status:** Both `xuple/evolayer-base` and `xuple/evolayer-base-starter` are pre-1.0 and currently staged on a private Forge plus a private GitHub mirror. The Packagist + public GitHub launch is the target. See [RELEASE.md](RELEASE.md) for what changes at tag day and how to install during the staging window.

## How the pieces fit

| | Owns | Posture |
| --- | --- | --- |
| [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) (package) | Examples, blocks, agents, ontology, `evolayer:*` commands, the `evolayer.base.*` config shape | Conservative — installs add no routes by default |
| `xuple/evolayer-base-starter` (this repo) | The Laravel host shell: Inertia/auth wiring, host migrations, `laravel/ai` patch, kitchen-sink `.env.example`, CI | Kitchen-sink — every example feature on out of the box |

The starter is a thin fork of `laravel/react-starter-kit`. It gives you a full Laravel application from day one — auth, host Inertia pages, React components, Tailwind styling, EvoLayer-published examples, config, seeders, and tests are all available to adapt. The examples are owned by the package and committed here so the starter clones and builds without an install step; see [Re-syncing the package frontend](#re-syncing-the-package-frontend) for how upstream changes flow in.

Public web strategy (post-launch): `evodevops.com` will be the editorial / teaching home for the EvoDevOps starter-kit family, and `docs.evodevops.com/base` will be the canonical EvoLayer Base documentation path. In this starter, `/` mounts the EvoLayer Base demo/install explainer; the package's opt-in marketing routes expose that same page at `/about`.

## What ships

- Laravel 13 + Fortify + React 19 + Inertia + TypeScript + Tailwind, following the official React starter structure.
- EvoLayer Base examples: ThreadStudio, PRD Studio, admin inbox, contact AI, voice input, text assist, and marketing pages.
- Structured-output streaming support through the committed `laravel/ai` patch.
- Spatie permission/activitylog/medialibrary/tags config and migrations committed where the host app must own them, including ULID-compatible morph columns for EvoLayer models.
- A seeded admin demo user plus AI capability ledger for immediate local exploration.

## Quick start

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app

# install + build the frontend, including SSR
npm install
npm run build        # or `npm run dev` for HMR

php artisan serve
```

`composer create-project` runs the post-create hook automatically (`key:generate`, create
the SQLite database, `migrate --seed`, `wayfinder:generate`, `evolayer:ontology:compile`). If you
clone this repo directly instead, run the equivalent in one shot:

```bash
composer setup
```

Private pre-release installs need access to the Forge/GitHub repositories described in [RELEASE.md](RELEASE.md). Once the packages are tagged and published, the same command works without the VCS repository setup.

### Demo login

The seeder creates an admin demo user so every example page (including the admin-only Inbox
and PRD Studio) is reachable immediately:

| Email              | Password   |
| ------------------ | ---------- |
| `test@example.com` | `password` |

## Features

Each bundled example surface is gated by an `EVOLAYER_BASE_EXAMPLE_*` flag in `.env`;
starter-level substrate features (medialibrary-backed attachments, etc.) use the
`EVOLAYER_BASE_FEATURE_*` prefix instead. Set a flag to `false` to drop that feature's
routes and hide its sidebar entry; the shared `evolayer.base.{examples,features}` Inertia
prop still carries the key but reports it as `false`, so client code can branch on it.
`.env.example` enables the kitchen-sink set explicitly so a fresh install shows the full
surface.

| Flag                                   | What it adds                                               |
| -------------------------------------- | ---------------------------------------------------------- |
| `EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES`     | Public Home / About launcher pages                         |
| `EVOLAYER_BASE_EXAMPLE_THREAD_STUDIO`       | ThreadStudio — streaming AI compose with structured output |
| `EVOLAYER_BASE_EXAMPLE_PRD_STUDIO`          | PRD Studio — turn notes into scoped requirements           |
| `EVOLAYER_BASE_EXAMPLE_ADMIN_INBOX`         | Admin inbox for contact-form submissions                   |
| `EVOLAYER_BASE_EXAMPLE_CONTACT_AI`          | AI-assisted contact form (triage, auto-tagging)            |
| `EVOLAYER_BASE_EXAMPLE_VOICE_INPUT`         | Voice-input block                                          |
| `EVOLAYER_BASE_EXAMPLE_AI_TEXT_FIELD`       | `<AiTextField>` block — inline streaming suggestions       |
| `EVOLAYER_BASE_FEATURE_CONTACT_ATTACHMENTS` | Contact-form attachment processing (uses medialibrary)     |

## AI providers

The AI features default to **Gemini**. Set a key for whichever provider you point
`AI_DEFAULT_PROVIDER` at, then verify structured streaming works end to end:

```bash
php artisan evolayer:ai:stream-smoke gemini
```

Keys live in `.env` (`GEMINI_API_KEY`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, …).

> **Provider status:** ThreadStudio's curated (directly-verified) providers are
> **Gemini** (default) and **OpenAI** — both pass `evolayer:ai:stream-smoke` end to end
> with the committed `laravel/ai` patch, and only these two are selectable as
> `AI_THREAD_STUDIO_PROVIDER`. Anthropic's structured-streaming path currently returns
> zero `TextDelta` events and an empty final payload, so it is **diagnostic-only / blocked
> for ThreadStudio** — exercise it with `evolayer:ai:smoke-test anthropic` (the
> non-streaming path passes), but it is not curated for selection. NVIDIA / OpenCode /
> OpenRouter are likewise OpenAI-compatible probe candidates, not curated. See
> [`patches/README.md`](patches/README.md) for the verification matrix.

## The `laravel/ai` patch

EvoLayer Base relies on structured-output **streaming**, which upstream `laravel/ai` does
not yet support. The fix is shipped as a composer patch:

- `patches/laravel-ai-structured-streaming.patch` is applied automatically on
  `composer install` / `composer update` via
  [`cweagans/composer-patches`](https://github.com/cweagans/composer-patches) (declared in
  `composer.json` → `extra.patches`, and allowed in `config.allow-plugins`).
- See `patches/README.md` for the rationale and the upstream-PR tracking note.

If structured streaming ever misbehaves, run `php artisan evolayer:doctor` — it verifies the
patch marker is present along with the rest of the install.

## What's pre-applied (host-side integration)

The `xuple/evolayer-base` package publishes most of its surface, but a few edits have to live in
host files. These are already applied in this template:

- `app/Http/Middleware/HandleInertiaRequests.php` — shares the `evolayer.base.{examples,features}` prop.
- `app/Models/User.php` — adds Spatie's `HasRoles` so the admin gate resolves `hasRole('admin')`.
- `resources/js/components/app-sidebar.tsx` — renders the enabled example pages via `useExampleNavItems()`.
- `resources/js/types/global.d.ts` — types the `evolayer` shared prop.
- `resources/js/app.tsx` — uses the `|` title separator.
- `database/seeders/DatabaseSeeder.php` — seeds the AI capability ledger and the admin demo user.

The Spatie packages (`laravel-permission`, `laravel-activitylog`, `laravel-medialibrary`,
`laravel-tags`) have their config and migrations committed under `config/` and
`database/migrations/`. EvoLayer Base's own migrations load from the package and run
automatically — they are not copied into this repo.

The committed activitylog, tags, and medialibrary migrations deliberately use ULID-compatible morph columns where they can point at EvoLayer models:

- `activity_log.subject_id` via `nullableUlidMorphs('subject', 'subject')`
- `taggables.taggable_id` via `ulidMorphs('taggable')`
- `media.model_id` via `ulidMorphs('model')`

Keep those edits if you regenerate Spatie migrations; PostgreSQL will reject EvoLayer ULIDs in default bigint morph columns.

## Re-syncing the package frontend

The EvoLayer React stubs are committed to this repo so it clones and builds without any
publish step. To pull a newer `xuple/evolayer-base` release's frontend over the top:

```bash
composer update xuple/evolayer-base
composer evolayer:resync   # re-publishes frontend + config, regenerates wayfinder + ontology
```

**Do not edit files under `vendor/xuple/evolayer-base` in this starter.** Package
internals belong in the [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) repo;
fix them there, tag a release (or update the local path override per
[RELEASE.md](RELEASE.md)), then `composer update` + `composer evolayer:resync` here to
pull the change.

To **add** EvoLayer Base to an existing Laravel app instead (rather than starting here), use
the package's own installer: `php artisan evolayer:install`. You don't need to run that
command in this starter — its work is already pre-applied.

## Tooling

- `composer dev` — run server, queue, logs, and Vite together.
- `php artisan evolayer:doctor` — health-check the install.
- `npm run types:check` / `npm run build` (client + SSR) / `composer lint` / `composer test`.

The starter is also pre-wired for AI coding agents (Claude Code, Codex, OpenCode, Cursor) via [Laravel Boost](https://laravel.com/docs/boost): `AGENTS.md` / `CLAUDE.md` carry the starter-specific boundaries followed by Boost's framework guidelines, and `.mcp.json` / `.codex/config.toml` / `opencode.json` register `php artisan boost:mcp` so agents can call `search-docs`, `tinker`, `database-query`, etc. Skills live under `.claude/skills/` and `.agents/skills/`. **Boost is a `require-dev` dependency**; the MCP layer is only available when the app is installed with dev dependencies (the `composer install` / `composer create-project` default — `composer install --no-dev` skips it).

The test runner is **PHPUnit** (`vendor/bin/phpunit`), not Pest. This is the inherited posture from `laravel/react-starter-kit` and has not yet been formally revisited for the EvoLayer pivot. Treat PHPUnit as the current contract — `composer test`, the kitchen-sink contract test, and CI all assume it — but the question of whether to migrate to Pest before public 0.1 is an open architectural decision tracked alongside the package-side closeout items.

## Where this sits in the EvoDevOps family

EvoLayer Base is the **AI / ontology / blocks substrate**: a Laravel + Inertia + React layer that turns the [`laravel/ai`](https://github.com/laravel/ai) SDK into a structured-output streaming surface, with an `ontology.yaml`-driven event/projection model and a small block library on top. This starter is the `composer create-project` entry point for Base. The package itself is [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) under the `evolayer.base.*` config and route namespace.

Sibling EvoDevOps layers (`evolayer.commerce.*`, `evolayer.saas.*`, `evolayer.rls.*`, …) are planned as separate packages with their own starter repos following the same pattern; they will not ship inside this Base starter. `evodevops.com` is the editorial / teaching home for the family; `docs.evodevops.com/base` is the canonical Base documentation root.

## Project Status

EvoLayer is pre-1.0. Base and the starter are free/public MIT projects; Forge and private GitHub are pre-launch staging, and Packagist is the public launch target. See [RELEASE.md](RELEASE.md) and [CHANGELOG.md](CHANGELOG.md).

---

Built on the [Laravel React Starter Kit](https://laravel.com/docs/starter-kits). Licensed
under the MIT license.
