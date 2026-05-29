# EvoLayer Base Starter

The [Laravel React Inertia starter kit](https://laravel.com/docs/starter-kits) with the
**EvoLayer Base** layer pre-wired: an AI/ontology/blocks substrate built on the
[`laravel/ai`](https://github.com/laravel/ai) SDK, ready to extend.

You get the full application code in your repo from day one: auth, Inertia pages,
React components, Tailwind styling, EvoLayer examples, config, seeders, and tests
are all yours to change.

This template ships in **kitchen-sink** posture — every example feature is enabled out of
the box so you can see the full surface immediately. Disable what you don't want by flipping
a single env flag (see [Features](#features)).

It is a thin fork of `laravel/react-starter-kit` that requires the
[`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) Composer package and pre-applies the small set of
host-side integration edits the package can't publish on its own.

Public web strategy: `evodevops.com` is the editorial/teaching home for the
starter-kit family, while `https://docs.evodevops.com/base` is the canonical
EvoLayer Base documentation path. In this starter, `/` mounts the EvoLayer Base
demo/install explainer; the package's opt-in marketing routes expose that same
page at `/about`.

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

# install + build the frontend
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

Each example feature is gated by an `EVOLAYER_BASE_EXAMPLE_*` flag in `.env`. Setting one to
`false` (or deleting the line) drops that feature's routes, sidebar entry, and shared props —
nothing else changes.

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

To **add** EvoLayer Base to an existing Laravel app instead (rather than starting here), use
the package's own installer: `php artisan evolayer:install`.

## Tooling

- `composer dev` — run server, queue, logs, and Vite together.
- `php artisan evolayer:doctor` — health-check the install.
- `npm run types:check` / `npm run build` / `composer lint` / `composer test`.

## Project Status

EvoLayer is pre-1.0. Base and the starter are free/public MIT projects; Forge and private GitHub are pre-launch staging, and Packagist is the public launch target. See [RELEASE.md](RELEASE.md) and [CHANGELOG.md](CHANGELOG.md).

---

Built on the [Laravel React Starter Kit](https://laravel.com/docs/starter-kits). Licensed
under the MIT license.
