# Changelog

All notable changes to `xuple/evolayer-base-starter` are documented here. The
format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/) and
this project aims to follow [Semantic Versioning](https://semver.org/).

## [Unreleased]

Provisional first release: **0.1.0** (not yet tagged or published — see
`RELEASE.md`).

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
- README "Provider status" callout flagging Anthropic structured streaming as
  a known-pending probe; Gemini and OpenAI remain the verified streaming paths.
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
- `ontology.yaml` `change_event` entity caught up to the package's actual
  schema: `actor_user_id` → `actor_type` + `actor_id`; `subject_id` is
  `ulid?` instead of `string?`; `created_at` / `updated_at` added. The
  remaining `actor: belongs_to → user` relation (vs the migration's
  polymorphic `nullableMorphs('actor')`) is filed for upstream.
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

### Notes
- During private pre-launch staging, the package is resolved from the Forge `vcs`
  repository at `dev-main` (composer.lock is not committed). At public launch it
  becomes `^0.1` from Packagist. See `RELEASE.md`. `composer create-project`
  verified end-to-end from the Forge.
