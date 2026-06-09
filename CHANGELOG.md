# Changelog

All notable changes to `xuple/evolayer-base-starter` are documented here. The
format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
this project aims to follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

## [0.1.0] - 2026-06-09

First public release — the publicly installable starter for the EvoLayer Base
**developer preview**. Pairs with `xuple/evolayer-base` v0.1.0 (consumed as
`^0.1` from Packagist). Pre-1.0; APIs may change before 1.0.

EvoLayer Base Starter — the Laravel React Inertia starter kit with the EvoLayer
Base layer pre-wired. Part of the EvoDevOps starter-kit family.

### Added
- `composer create-project xuple/evolayer-base-starter` scaffold, a thin fork of
  `laravel/react-starter-kit` requiring `xuple/evolayer-base`.
- Kitchen-sink posture: every `EVOLAYER_BASE_EXAMPLE_*` flag enabled in
  `.env.example`; committed EvoLayer frontend so the repo clones and builds.
- Host-side integration pre-applied: `evolayer` shared prop in
  `HandleInertiaRequests`, `HasRoles` on `User`, `useExampleNavItems()` in the
  sidebar, `evolayer` prop typing, `|` title separator, and a `DatabaseSeeder`
  that seeds the AI capability ledger plus an admin demo user
  (`test@example.com`).
- `laravel/ai` structured-streaming patch committed to `patches/` and applied via
  `cweagans/composer-patches` (`extra.patches`, root-relative).
- Spatie config + migrations committed (`permission`, `activitylog`,
  `medialibrary`, `tags`); EvoLayer migrations auto-load from the package.
- Minimal post-create hook (`key:generate`, sqlite, `migrate --seed`,
  `wayfinder:generate`, `evolayer:ontology:compile`) and a `composer evolayer:resync`
  script to re-publish the package frontend on upgrade.
- Public contribution, security, and support policies (`CONTRIBUTING.md`,
  `SECURITY.md`, `SUPPORT.md`).
- `tests/Feature/KitchenSinkContractTest.php` locks the documented kitchen-sink
  install posture: every `EVOLAYER_BASE_*` env key read by `config/evolayer.php`
  is set to `true` in `.env.example`, the shared `evolayer.base.{examples,features}`
  Inertia prop exposes them all, and disabling a flag propagates to the prop.
- README section "Where this sits in the EvoDevOps family" positioning Base as
  the AI / ontology / blocks substrate, with sibling layers (Commerce / SaaS /
  RLS) planned in their own starter repos.
- Agent-support tooling: `AGENTS.md` and `CLAUDE.md` (byte-identical) carrying the
  package/starter boundary, feature-flag conventions, and verification suite;
  Laravel Boost as a `require-dev` dependency with `.mcp.json`, `.codex/config.toml`,
  and `opencode.json` wiring `php artisan boost:mcp` for Claude Code, Codex, and
  OpenCode; six Boost skills published under `.claude/skills/` and `.agents/skills/`.
  The MCP layer assumes dev dependencies — `composer install --no-dev` omits Boost.
  The test runner is documented as PHPUnit-first.
- `CONTRIBUTING.md` "Where does my change belong?" routing matrix mapping
  concrete paths to starter / package / both, plus an explicit rule against
  editing `vendor/xuple/evolayer-base` from this starter (also surfaced in
  the README).
- `CONTRIBUTING.md` "Feature-flag conventions" section distinguishing
  `EVOLAYER_BASE_EXAMPLE_*` (bundled demo surfaces) from
  `EVOLAYER_BASE_FEATURE_*` (substrate capabilities), with PR documentation
  requirements for new flags.
- `CONTRIBUTING.md` "Testing scope" section: the starter ships PHPUnit
  Feature/HTTP coverage only; browser/E2E coverage belongs in the package.
- Starter CI fails the build if `composer.lock` is committed to the repo, and
  the tests workflow enforces `evolayer:doctor` strictness — any advisory from
  the (otherwise informational, zero-exit) doctor command now fails the build.

### Changed
- `composer evolayer:resync` now also publishes the `evolayer-base-ontology`
  tag, so `ontology.yaml` refreshes alongside the frontend and config when a
  newer `xuple/evolayer-base` release lands. Previously the ontology stub
  stayed stale through resync until manually re-published.
- `ontology.yaml` `change_event` entity now fully matches the package's published
  schema after resync: `actor_user_id` → `actor_type` + `actor_id`; the `actor`
  relation is `morph_to → any` (was `belongs_to → user`, vs the migration's
  polymorphic `nullableMorphs('actor')`); `tenant_id: string?` is present;
  `subject_id` is `ulid?`; `created_at` / `updated_at` added.
- `ontology.yaml` now carries the package's broader ontology↔migration sync
  corrections: `form_submission.honeypot`, AI invocation subject/tenant/cost/
  duration fields, attempt provider metadata, `ai_capability.conditions`, and
  timestamps are declared; AI invocation statuses now match runtime
  (`started` / `succeeded` / `failed`); and provider/model details live on
  invocation attempts rather than the parent invocation entity.
- `npm run build` defaults to the combined client + SSR build (`build` runs
  `build:ssr`).
- Spatie host-owned migrations use ULID-compatible morph columns
  (`nullableUlidMorphs` / `ulidMorphs` on `activity_log.subject_id`,
  `taggables.taggable_id`, `media.model_id`) so PostgreSQL hosts can point them
  at EvoLayer ULID models.
- Vite production output splits vendor chunks.
- README "Features" intro distinguishes example surfaces
  (`EVOLAYER_BASE_EXAMPLE_*`) from substrate capabilities
  (`EVOLAYER_BASE_FEATURE_*`); the `CONTACT_ATTACHMENTS` flag was previously
  described under the `EXAMPLE_*` prefix by mistake.
- README opening softened: the starter is a Laravel host application carrying
  EvoLayer-published examples, not a copy of package internals.
- ThreadStudio provider posture aligned to the package's Verified Runtime Strategy:
  README, `.env.example`, and the resynced `config/evolayer-ai.php` describe
  **Gemini** (default) and **OpenAI** as the runtime-approved/selectable ThreadStudio
  providers; **Anthropic** as diagnostic-eligible but blocked for ThreadStudio
  runtime / pending re-verification (structured streaming emits no usable
  `TextDelta` events); and **NVIDIA / OpenCode / OpenRouter** as router-backed
  diagnostic-eligible probe candidates, not runtime-approved.
  `OPENAI_CHAT_MODEL` (`gpt-4o-mini` default) added so OpenAI resolves a default
  model. The published config and ThreadStudio frontend were resynced to the
  package's Verified Runtime Strategy snapshot.
- Starter CI generates the Wayfinder helpers and compiles the ontology (after
  creating + migrating the SQLite database) before `npm run types:check` and
  `npm run build` — without these the gitignored generated route/action/ontology
  types are absent on a fresh CI checkout.
- Starter CI consumes the package's `evolayer:doctor --strict --no-ansi`
  exit-code contract directly, replacing the earlier grep-the-summary-line
  wrapper.
- `composer setup` creates the SQLite database file before migrating, so a fresh
  direct clone sets up without a manual `touch`.
- Frontend identity fixes: the `Repository` / `Documentation` links point at the
  EvoLayer Base repo and docs (not the upstream Laravel kit); `welcome.tsx` is
  documented as the inherited Laravel-kit fallback retained for the chisel
  auth-trim flow; README opening tightened and public-facing URLs marked
  post-launch.

### Notes
- During private pre-launch staging, the package is resolved from the Forge `vcs`
  repository at `dev-main` (composer.lock is not committed). At public launch it
  becomes `^0.1` from Packagist. See `RELEASE.md`. `composer create-project`
  verified end-to-end from the Forge.
