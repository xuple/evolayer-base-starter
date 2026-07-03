# Contributing

Thanks for helping improve EvoLayer Base Starter.

The starter is the free public `composer create-project` entry point for EvoLayer Base. It intentionally ships in kitchen-sink posture so developers can see the full demo surface immediately, while the package itself remains opt-in by default.

## Ground Rules

- Do not commit secrets, `.env`, `auth.json`, credentials, API keys, generated private keys, or local Composer auth.
- Keep pre-release private repository access explicit. Do not commit machine-local path repositories.
- Preserve the public identity: starter `xuple/evolayer-base-starter`, package `xuple/evolayer-base`, route names `evolayer.base.*`.
- Commit `composer.lock`; the starter ships a tested, reproducible distribution and `composer create-project` installs the locked graph. Keep `xuple/evolayer-base` exact-pinned while `0.x`.
- Prefer small, reviewable PRs with tests or a clear explanation of why tests are not applicable.

## Accepted asymmetries (starter ↔ package)

These differences between this starter and the upstream `xuple/evolayer-base` package are **deliberate, not drift** — don't "fix" them to match:

- **Patch mechanism.** Both repos carry the same `patches/laravel-ai-structured-streaming.patch` and its `patches/README.md` dossier, but apply it differently: the starter uses the `cweagans/composer-patches` plugin (`extra.patches` in `composer.json`), which a root application can carry as a dev dependency, while the package uses a hand-rolled `apply-patches.php` composer script (kept dependency-free, as befits a library). Same patch, mechanism chosen to fit each repo's role.

(The former test-runner asymmetry is gone: both repos are now Pest-first.)

## Where does my change belong?

This starter is a thin host over the [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) package. Most product surface lives upstream; the starter owns the Laravel host shell that wires it in. Use this matrix before opening a PR:

| Change                                                                                                                                                                                                                                                        | Where it goes                                                                            |
| ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- | ---------------------------------------------------------------------------------------- |
| Example pages, routes, AI agents, blocks (`resources/js/pages/evolayer/**`, `resources/js/blocks/**`, `resources/js/hooks/use-evolayer-*`, `resources/js/hooks/use-example-nav-items.ts`, `resources/js/types/evolayer.d.ts`)                                 | Package                                                                                  |
| `config/evolayer.php` shape, defaults, env-key names                                                                                                                                                                                                          | Package                                                                                  |
| `ontology.yaml`, `evolayer:*` artisan commands (`doctor`, `install`, `ai:*`, `ontology:compile`)                                                                                                                                                              | Package                                                                                  |
| EvoLayer migrations and models                                                                                                                                                                                                                                | Package                                                                                  |
| Host integration files (`app/Http/Middleware/HandleInertiaRequests.php`, `app/Models/User.php`, `routes/web.php`, `resources/js/components/app-sidebar.tsx`, `resources/js/app.tsx`, `resources/js/types/global.d.ts`, `database/seeders/DatabaseSeeder.php`) | Starter                                                                                  |
| Public site metadata contract (`config/site.php`, `app/Support/SiteMetadata.php`, `resources/js/components/site-head.tsx`, `resources/js/lib/site-meta.ts`, `public/social/og-default.png`)                                                        | Starter                                                                                  |
| Spatie host migrations and ULID-morph schema choices                                                                                                                                                                                                          | Starter                                                                                  |
| `composer create-project` flow, post-create hooks, `composer setup`, `composer evolayer:resync`                                                                                                                                                               | Starter                                                                                  |
| Vendor patch wiring (`extra.patches`, `patches/`, `patches.lock.json`)                                                                                                                                                                                        | Starter                                                                                  |
| `.env.example` values (including kitchen-sink defaults)                                                                                                                                                                                                       | Starter                                                                                  |
| Starter CI (`.github/workflows/*.yml`), starter tests (`tests/Feature/**`, `tests/Unit/**`)                                                                                                                                                                   | Starter                                                                                  |
| New `EVOLAYER_BASE_*` env flag                                                                                                                                                                                                                                | Both — declare the key + default in package config, set the value in `.env.example` here |
| Public-facing docs that affect both surfaces                                                                                                                                                                                                                  | Both                                                                                     |

The EvoLayer React stubs listed in the first row are committed here so the starter clones and builds without a publish step, but they are **package-owned** — edits go upstream and `composer evolayer:resync` pulls them in. The same rule applies to `vendor/xuple/evolayer-base`: do not edit it from this starter; fix package internals in the package repo, then `composer update` + `composer evolayer:resync` here.

**Provider runtime approval and published EvoLayer surfaces are package-owned.** ThreadStudio provider approval (which providers are selectable, which are diagnostic/blocked) and the published stubs that carry it (`config/evolayer-ai.php`, `resources/js/pages/evolayer/**`, `ontology.yaml`) change in `xuple/evolayer-base` first — update the package, resync the starter, then update starter docs and release notes (`.env.example`, `README.md`, `CHANGELOG.md`, `RELEASE.md`). Do not hand-edit the runtime-approved roster into starter stubs or agent docs.

**The public explainer is package-owned and branded from config; the authenticated Home is host-owned.** `resources/js/pages/evolayer/base.tsx` (the public explainer at `/`) renders from `useBrand()` — `config('evolayer.base.brand')`, shared via `EvoLayerProps::base()` and surfaced from `EVOLAYER_BASE_BRAND_*` in `.env.example`. Public landing chrome also reads `useBrand()`, and blank `SITE_NAME` / `SITE_TITLE_TEMPLATE` values inherit that brand before falling back to `APP_NAME`. Rebrand it by changing config, not by editing the page file. `composer evolayer:resync` runs `php artisan evolayer:resync`, which is manifest-safe — it keeps host-modified stubs (`--force` to overwrite, `--dry-run` to preview) and skips ejected surfaces. To own the marketing surface outright, run `php artisan evolayer:eject marketing-pages` (forfeiting managed updates for it). The authenticated launcher `resources/js/pages/home.tsx` is **starter-owned** (not under `evolayer/**`); all other `resources/js/pages/evolayer/**` files are package-owned.

If a change spans both repos (most commonly: a new `EVOLAYER_BASE_*` flag, or a host edit that requires a package change), land the package PR first against a resolvable ref the starter can pick up, then open the starter PR pointing at it.

### Adding starter routes or pages safely

- Use the public `welcome` route (`/`) for public landing/logout flows.
- Public `/` (route `welcome`) renders the package-owned `evolayer/base` starter
  explainer; `welcome.tsx` is only a retained Laravel-kit / chisel compatibility
  artefact, not the active public IA.
- The authenticated launcher `/home` (route `home`) is **host-owned** by the starter
  and always present — it is not gated by `EVOLAYER_BASE_EXAMPLE_MARKETING_PAGES`, so
  disabling marketing pages never breaks the shell build. `config/fortify.php`
  redirects to `/home`. Never use `home()` in public chrome — it now means the
  authenticated launcher.
- Put host shell pages/routes in starter-owned files. Put new EvoLayer examples, blocks, agents, or package routes in `xuple/evolayer-base` first, then resync here.
- Public pages should use `PublicLayout` / `SiteHead` for title, canonical, robots, Open Graph, X/Twitter-compatible, and JSON-LD tags. Do not scatter page-local SEO/social literals across page components.
- Read social/metadata env values only in `config/site.php`. Runtime code should use `config('site.*')`, `App\Support\SiteMetadata`, or the shared `site` Inertia prop.
- Prefer the opt-in, off-by-default SEO/asset knobs over hand-rolling: `SITE_JSONLD_TYPE` (+ optional contact fields) emits a server-rendered local-business JSON-LD node via `SiteMetadata::defaultJsonLd()`; `SITE_ASSET_VERSION` + the `useVersionedAsset()` hook cache-bust public asset URLs. Reach for a page-level `SiteHead` `jsonLd` override or a bespoke version constant only when these config paths genuinely don't fit.
- After route changes, clear stale route caches before regenerating typed frontend contracts:

```bash
php artisan route:clear
php artisan wayfinder:generate --with-form --no-interaction
php artisan evolayer:ontology:compile --no-erd --no-interaction
```

## Feature-flag conventions

The starter uses two prefixes, both static and `.env`-driven, read through `config/evolayer.php` and surfaced on the shared `evolayer.base.{examples,features}` prop:

- `EVOLAYER_BASE_EXAMPLE_*` — bundled demo/example surfaces (ThreadStudio, PRD Studio, admin inbox, marketing pages, etc.).
- `EVOLAYER_BASE_FEATURE_*` — starter-level substrate capabilities (e.g. medialibrary-backed contact attachments).

These are install-time/demo-surface switches, not per-user, gradual-rollout, A/B, or billing-entitlement flags. If you need any of those, reach for [Laravel Pennant](https://laravel.com/docs/pennant) in a host application — not these.

When adding a new flag, document in the PR:

- Category (`EXAMPLE_*` or `FEATURE_*`) and why it belongs there.
- Default value in `config/evolayer.php` (package) **and** the `.env.example` value here (starter).
- Surfaces it gates: routes, sidebar entry, shared-prop key, blocks.
- Owner and whether the flag is intended to be permanent or removable once the feature stabilises.

Disabled must mean inaccessible, not merely hidden — flipping a flag to `false` should drop the route, hide the sidebar entry, and surface the flag as `false` on the shared `evolayer.base.{examples,features}` prop (the key stays; only its value flips). Cover that in tests where the flag is registered (route gating is the package's responsibility; the starter's kitchen-sink contract test covers the shared-prop side).

## Testing scope

The starter tests are **Feature/HTTP only**, run via Pest on top of PHPUnit. They cover the host shell — auth, settings, dashboard, the kitchen-sink install contract, and any host integration that lives in this repo.

There is no browser/E2E tooling in this starter (no Dusk, no Playwright) and none planned for pre-1.0. The example UIs that would justify browser coverage live in [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base); if a browser harness is added, it lands there alongside the components it exercises, not here. Don't introduce starter-local Dusk/Playwright/Cypress configuration — open the discussion in the package repo first.

## Local Checks

Run these before opening a PR:

```bash
composer validate --strict
npm run types:check
npm run build
php artisan evolayer:doctor
composer test
```

For formatting and lint-only changes, also run:

```bash
composer lint:check
npm run format:check
npm run lint:check
```

## Documentation

Update README, CHANGELOG, or RELEASE when changing install flow, route/page IA, feature flags, CI behaviour, package resolution, commands, or public release posture.

The public documentation lives at [`evodevops.com/evolayer-base/docs`](https://evodevops.com/evolayer-base/docs) in the `xuple/evodevops` site repo. When starter changes alter any of these surfaces, update the matching site page in the same release window:

| Starter change | Site page to check |
| --- | --- |
| `composer create-project`, setup hooks, or committed-lock policy | `tutorial/first-install`, `explanation/reproducible-installs` |
| `composer evolayer:resync`, package bump workflow, or exact-pin policy | `how-to/update-the-framework`, `how-to/resync-the-frontend` |
| `.env.example` `EVOLAYER_BASE_*` values | `reference/env-flags`, `how-to/enable-a-feature`, `how-to/disable-a-feature` |
| `PROMISE.md` | `explanation/promise` |
| Host integration files listed in the ownership matrix | `reference/host-integration-steps` |

## Pull Request Checklist

- Tests or verification notes included.
- Public docs updated where needed.
- `composer.lock` is committed and in sync (`composer validate --strict`), with `xuple/evolayer-base` exact-pinned while `0.x`.
- No local path repository or private credential committed.
- No stale `EvoDevOps Base` or old `evodevops/base` package naming introduced.
