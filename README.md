# EvoLayer Base Starter

A kitchen-sink Laravel + React + Inertia starter for exploring **EvoLayer Base** — Xuple's AI, ontology, and blocks substrate for Laravel apps, built on the [`laravel/ai`](https://github.com/laravel/ai) SDK.

- **Start here** when you want a full demo app with EvoLayer Base already wired in, every example surface visible.
- **Use the [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) package directly** when adding Base to an existing Laravel app.

> **Developer preview:** Both `xuple/evolayer-base` and `xuple/evolayer-base-starter` are public, MIT-licensed, pre-1.0 packages on GitHub and Packagist. The current public install line is starter `v0.1.7` with `xuple/evolayer-base` `v0.1.4`.

## How the pieces fit

|                                                                           | Owns                                                                                                              | Posture                                                         |
| ------------------------------------------------------------------------- | ----------------------------------------------------------------------------------------------------------------- | --------------------------------------------------------------- |
| [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) (package) | Examples, blocks, agents, ontology, `evolayer:*` commands, the `evolayer.base.*` config shape                     | Conservative — installs add no routes by default                |
| `xuple/evolayer-base-starter` (this repo)                                 | The Laravel host shell: Inertia/auth wiring, host migrations, `laravel/ai` patch, kitchen-sink `.env.example`, CI | Kitchen-sink — every example feature switched on out of the box |

The starter is a thin fork of `laravel/react-starter-kit`. It gives you a full Laravel application from day one — auth, host Inertia pages, React components, Tailwind styling, EvoLayer-published examples, config, seeders, and tests are all available to adapt.

> **Framework Contract**: For the strict definition of what the framework manages versus what you own, see the [EvoLayer Framework Contract](https://github.com/xuple/evolayer-base/blob/main/docs/contract.md) in the upstream package.

Public web strategy (post-launch): `evodevops.com` will be the editorial / teaching home for the EvoDevOps starter-kit family, and `evodevops.com/evolayer-base/docs` will be the canonical EvoLayer Base documentation path. In this starter, `/` mounts the EvoLayer Base demo/install explainer; the package's opt-in marketing routes expose that same page at `/about`.

Authenticated users land on `/home` (`evolayer.base.home`) after login, registration, password reset, and passkey authentication. The public `/` route remains named `home` and is still the post-logout / public landing destination.

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
the SQLite database, `migrate --seed`, `route:clear`, `wayfinder:generate`, `evolayer:ontology:compile`). If you
clone this repo directly instead, run the equivalent in one shot:

```bash
composer setup
```

The starter commits `composer.lock` and ships a tested, reproducible
distribution: `composer create-project` installs the locked dependency graph
rather than re-resolving it. Created applications likewise commit their
`composer.lock` for deterministic deploys.

Hosting the created app behind Nginx/PHP-FPM? See
[`docs/local-dev-hosting.md`](docs/local-dev-hosting.md) for the write-permission
handoff, SQLite file notes, and env-driven Vite port/origin HMR workflow.

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
surface. If a downstream app disables `EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES`, choose a
replacement authenticated landing route and update `fortify.home` away from the
starter's default `/home` before removing that route.

| Flag                                        | What it adds                                               |
| ------------------------------------------- | ---------------------------------------------------------- |
| `EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES`     | Public About and authenticated Home launcher pages         |
| `EVOLAYER_BASE_EXAMPLE_THREAD_STUDIO`       | ThreadStudio — streaming AI compose with structured output |
| `EVOLAYER_BASE_EXAMPLE_PRD_STUDIO`          | PRD Studio — turn notes into scoped requirements           |
| `EVOLAYER_BASE_EXAMPLE_ADMIN_INBOX`         | Admin inbox for contact-form submissions                   |
| `EVOLAYER_BASE_EXAMPLE_CONTACT_AI`          | AI-assisted contact form (triage, auto-tagging)            |
| `EVOLAYER_BASE_EXAMPLE_VOICE_INPUT`         | Voice-input block                                          |
| `EVOLAYER_BASE_EXAMPLE_AI_TEXT_FIELD`       | `<AiTextField>` block — inline streaming suggestions       |
| `EVOLAYER_BASE_FEATURE_CONTACT_ATTACHMENTS` | Contact-form attachment processing (uses medialibrary)     |

## Social previews and site metadata

Public link-preview defaults live in `config/site.php` and are documented in
`.env.example` with `SITE_*` and `SOCIAL_*` variables. Keep environment reads in
config files only; pages and components should consume `config('site.*')` on the
server or the namespaced `site` Inertia prop on the client.

The default canonical base is `APP_URL`. Set `SITE_URL` only when the public
share/search origin differs from the runtime app origin. Social images may be an
absolute URL or a leading-slash path resolved against that canonical base; they
do not automatically inherit from `ASSET_URL`.

Public pages should use `PublicLayout`, which renders `SiteHead` for title,
description, canonical, robots, Open Graph, X/Twitter-compatible, theme-colour,
and JSON-LD defaults. Page-level overrides belong in `SiteHead`/`PublicLayout`
props, not scattered literal `<meta>` tags. Authenticated and auth layouts use a
`noindex,nofollow` robots override and do not emit public preview metadata.

The starter ships `public/social/og-default.png` as the default 1200x630 preview
image. Replace it in downstream apps when their brand is ready, then update
`SOCIAL_IMAGE_ALT` and optionally `SOCIAL_IMAGE_VERSION` to force preview-card
refreshes.

## AI providers

EvoLayer Base uses the `laravel/ai` SDK for structured-output streaming. It defaults to **Gemini**, but supports a vast ecosystem including OpenAI, Anthropic, DeepSeek, Groq, xAI, Mistral, and Ollama. To enable AI features, set your provider's API key in `.env` (`GEMINI_API_KEY`, `OPENAI_API_KEY`, `ANTHROPIC_API_KEY`, etc.), then verify structured streaming works end to end:

```bash
php artisan evolayer:ai:stream-check gemini
```

> **Provider status:** ThreadStudio's runtime-approved (directly-verified) providers are
> **Gemini** (default) and **OpenAI** — both pass `evolayer:ai:stream-check` end to end
> with the committed `laravel/ai` patch, and only these two are selectable as
> `AI_THREAD_STUDIO_PROVIDER`. Anthropic's structured-streaming path currently returns
> zero `TextDelta` events and an empty final payload, so it is
> **diagnostic-eligible but blocked for ThreadStudio runtime / pending
> re-verification** — exercise it with `evolayer:ai:smoke-test anthropic` (the
> non-streaming path passes), but it is not runtime-approved for selection.
> NVIDIA / OpenCode / OpenRouter are likewise router-backed diagnostic-eligible
> probe candidates, not runtime-approved. See [`patches/README.md`](patches/README.md)
> for the verification matrix.

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
- [`docs/local-dev-hosting.md`](docs/local-dev-hosting.md) — Nginx/PHP-FPM
  hosted-dev checklist, `tempnam()` troubleshooting, and env-driven Vite
  port/origin HMR guidance.

The starter is also pre-wired for AI coding agents (Claude Code, Codex, OpenCode, Cursor) via [Laravel Boost](https://laravel.com/docs/boost): `AGENTS.md` / `CLAUDE.md` carry the starter-specific boundaries followed by Boost's framework guidelines, and `.mcp.json` / `.codex/config.toml` / `opencode.json` register `php artisan boost:mcp` so agents can call `search-docs`, `tinker`, `database-query`, etc. Skills live under `.claude/skills/` and `.agents/skills/`. **Boost is a `require-dev` dependency**; the MCP layer is only available when the app is installed with dev dependencies (the `composer install` / `composer create-project` default — `composer install --no-dev` skips it).

The test runner is **Pest 4** (`php artisan test`), layered on PHPUnit 12. New tests use Pest's `it()` / `test()` style (`php artisan make:test --pest {name}`); existing PHPUnit `Tests\TestCase` classes still run under Pest, so migration is opportunistic. `composer test`, the kitchen-sink contract test, and CI all run the Pest suite.

## Where this sits in the EvoDevOps family

EvoLayer Base is the **AI / ontology / blocks substrate**: a Laravel + Inertia + React layer that turns the [`laravel/ai`](https://github.com/laravel/ai) SDK into a structured-output streaming surface, with an `ontology.yaml`-driven event/projection model and a small block library on top. This starter is the `composer create-project` entry point for Base. The package itself is [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) under the `evolayer.base.*` config and route namespace.

Sibling EvoDevOps layers (`evolayer.commerce.*`, `evolayer.saas.*`, `evolayer.rls.*`, …) are planned as separate packages with their own starter repos following the same pattern; they will not ship inside this Base starter. `evodevops.com` is the editorial / teaching home for the family; `evodevops.com/evolayer-base/docs` is the canonical Base documentation root.

## Project Status

EvoLayer is pre-1.0. Base and the starter are free/public MIT projects published on GitHub and Packagist. See [RELEASE.md](RELEASE.md) and [CHANGELOG.md](CHANGELOG.md).

---

Built on the [Laravel React Starter Kit](https://laravel.com/docs/starter-kits). Licensed
under the MIT license.
