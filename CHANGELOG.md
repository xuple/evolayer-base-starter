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

### Notes
- During development the package is resolved from a local path repository
  (`../evodevops-base-pkg`) with `xuple/evolayer-base: *@dev`; this is replaced by
  a real version constraint once the package is tagged/published (see
  `RELEASE.md`).
