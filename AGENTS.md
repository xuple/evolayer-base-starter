# Agents Guide — EvoLayer Base Starter

For AI coding agents (Claude Code, Codex, OpenCode, Cursor, Aider, …) and any automation operating on this repo. Humans should start with [`README.md`](README.md) and [`CONTRIBUTING.md`](CONTRIBUTING.md).

This file is the short, prescriptive version of those documents tuned for agent decision-making. When in doubt about an architectural rule, the routing matrix in [`CONTRIBUTING.md`](CONTRIBUTING.md) is the source of truth.

**Read order.** The project-specific guidance below is the authoritative section for _this_ starter — package/starter boundaries, feature-flag conventions, patch policy, and out-of-scope rules. Generic Laravel / Inertia / React / Wayfinder / Pint guidelines from [Laravel Boost](https://laravel.com/docs/boost) follow in the second half of the file, in an auto-regenerated block at the bottom. When the two sections disagree, the project-specific guidance wins — Boost's framework rules are background, not foreground. The Boost-generated block is rewritten in place by `php artisan boost:update`; never edit content inside it (rules placed there are silently wiped on the next run). This file is mirrored byte-identically to `CLAUDE.md` so agents that look for either filename find the same content.

**Agent tooling assumes dev dependencies are installed.** Boost itself is a `require-dev` dependency, and the multi-agent MCP layer (Claude Code `.mcp.json`, Codex `.codex/config.toml`, OpenCode `opencode.json`) all route to `php artisan boost:mcp`. If the app was installed with `composer install --no-dev` (typical for production deploys), Boost is absent and the MCP server is unavailable to agents — the committed skill directories under `.claude/skills/` and `.agents/skills/` still discover, but live doc lookup (`search-docs`), `tinker`, `database-query`, and `browser-logs` will not work. For agent-assisted development, install with dev dependencies (`composer install` or `composer create-project`, default mode).

**Test runner is Pest (Pest-first).** This starter ships Pest 4 (`pestphp/pest`) layered on PHPUnit 12; `composer test` runs `php artisan test` (Pest). Write new tests in Pest's `it()` / `test()` style and scaffold them with `php artisan make:test --pest {name}`. Existing PHPUnit `Tests\TestCase` classes still run under Pest, so conversion is opportunistic — do not mass-rewrite green tests. `php artisan test` stays the public command; `boost.json` carries the `pest-testing` skill.

## What this repo is

`xuple/evolayer-base-starter` is the public `composer create-project` host application for **EvoLayer Base** — Xuple's AI / ontology / blocks substrate for Laravel + React + Inertia. Two repos work together:

| Repo                                                            | Role                                                                                                                                                                                                                                                    |
| --------------------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| [`xuple/evolayer-base`](https://github.com/xuple/evolayer-base) | The package. Owns examples, agents, blocks, ontology, `evolayer:*` artisan commands, and the `evolayer.base.*` config shape. Conservative — installs add no routes by default.                                                                          |
| `xuple/evolayer-base-starter` (this repo)                       | Thin Laravel host shell. Owns the integration files the package can't publish, the kitchen-sink `.env.example` defaults, the `laravel/ai` patch wiring, host-side migrations, and starter CI. Kitchen-sink — every demo surface enabled out of the box. |

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
- `config/site.php`, `app/Support/SiteMetadata.php`, `resources/js/components/site-head.tsx`, `resources/js/lib/site-meta.ts`, `public/social/og-default.png` — public site metadata and preview-card contract.
- `database/seeders/DatabaseSeeder.php`, `database/migrations/2026_05_24_*` — host-owned migrations (Spatie permission / activitylog / media / tags with ULID-compatible morph columns).
- `.env.example`, `composer.json` scripts (`setup`, `dev`, `evolayer:resync`, `post-create-project-cmd`, etc.).
- `patches/laravel-ai-structured-streaming.patch`, `patches.lock.json` (via `extra.patches` + `cweagans/composer-patches`).
- `scripts/resync-starter-pages-check.sh` — post-resync guard that verifies `_STARTER_OWNED_PAGE_` sentinels.
- `.github/workflows/*`, `tests/Feature/**`, `tests/Unit/**`.

**Package (edit upstream, never here):**

- `vendor/xuple/evolayer-base/**` — including all `resources/js/pages/evolayer/**`, `resources/js/blocks/**`, `resources/js/hooks/use-evolayer-*`, the ontology, agents, and every `evolayer:*` artisan command.
- The `evolayer.base.*` config shape (`config/evolayer.php` keys + defaults are package-owned, values in `.env.example` are starter-owned).

**Exception — starter-owned landing pages:** `resources/js/pages/evolayer/about.tsx` and `resources/js/pages/evolayer/home.tsx` are starter-owned brand overrides of the package's defaults. `composer evolayer:resync` uses the package's `evolayer-base-frontend-preserve-overrides` tag to refresh package-owned frontend stubs without overwriting them. That tag must exist in the installed `xuple/evolayer-base` version, so land/tag the package change before relying on this starter script. Both files carry a `_STARTER_OWNED_PAGE_` sentinel comment. `composer evolayer:resync` runs `scripts/resync-starter-pages-check.sh` after publishing, which fails loudly if the sentinel is missing (meaning the safe publish path was bypassed or the starter override was edited incorrectly). If the check fails, recover with:

```bash
git checkout -- resources/js/pages/evolayer/about.tsx resources/js/pages/evolayer/home.tsx
bash scripts/resync-starter-pages-check.sh
```

Agents must not assume starter-owned landing pages survived a resync unless the check passes. All other `resources/js/pages/evolayer/**` files are package-owned.

## Hard rules

- **Commit `composer.lock`.** The starter ships a tested, reproducible distribution: `composer create-project` installs the locked graph (Composer honors a committed lock — see `docs/migration/create-project-lock-behavior.md`). The lock must stay tracked and must not be `export-ignore`d (both CI workflows enforce this); `xuple/evolayer-base` is exact-pinned while `0.x`. Bump it via a release PR, not by letting installs drift. Generated apps commit their own lock too.
- **Do not edit anything under `vendor/`.** Patches go via `patches/` + `cweagans/composer-patches`; package fixes go upstream.
- **Do not introduce starter-local Dusk/Playwright/Cypress.** The starter ships Pest Feature/HTTP tests only; browser/E2E coverage belongs in the package alongside the components it exercises.
- **Do not change `config/evolayer.php` defaults to `true`** to make tests easier. The package keeps defaults `false`; `.env.example` is the kitchen-sink switch.
- **Do not run `php artisan evolayer:install` in this starter.** That command is for adding Base to an existing Laravel app; its work is already pre-applied here. Use `composer evolayer:resync` to pull a newer package frontend instead.
- **Do not push to any remote unless explicitly instructed.** Agents may create local commits only when asked. If asked to push, the agent must state which remote(s) and branch it will push to before running `git push`.
- **Do not scatter SEO/social-preview literals across pages.** Keep env reads in `config/site.php`; use `SiteHead` / `PublicLayout` for public pages, `SiteMetadata` for server fallback values, and keep `.env.example`, shared `site` types, tests, and docs aligned.

## Frontend stub flow

The package publishes React stubs into the starter via `vendor:publish --tag=evolayer-base-frontend-preserve-overrides` so the starter clones and builds without an install step while preserving starter-owned landing pages. These stubs are package-owned but live in this repo. When they regress format (Prettier's `prettier-plugin-tailwindcss` reorders Tailwind classes that the package doesn't pre-normalise), the mechanical fix is `npx prettier --write resources/ && eslint . --fix`. The kitchen-sink contract test does not depend on stub content; only the `EVOLAYER_BASE_*` flag shape.

## Inertia layout resolver

`resources/js/app.tsx` registers a `createInertiaApp` layout resolver that defaults to `AppLayout` (sidebar shell) for any page outside `auth/` and `settings/`, and `null` for `welcome` (the inherited Laravel-kit fallback). Per-page layouts override the resolver, but **the layout function must return a new JSX element**:

- ✅ `Page.layout = (page: ReactElement) => <>{page}</>;` — pages with their own full-page chrome (marketing / landing).
- ✅ `Page.layout = (page: ReactElement) => <PublicLayout>{page}</PublicLayout>;` — pages using the shared public shell at `resources/js/layouts/public-layout.tsx`.
- ❌ `Page.layout = (page) => page;` — Inertia does not recognise the bare ReactElement as a render function, falls back to the resolver, and re-wraps the page in `AppLayout` (sidebar visible on public pages).

Use `|` as the title separator, not `-`. The resolver sets it via `title: (title) => (title ? \`${title} | ${appName}\` : appName)`.

### Layout-prop typing policy

Pages in this starter use three layout declaration patterns. The choice depends on what the page needs from its layout:

1. **Static object layout** — `Page.layout = { breadcrumbs: [...] }` or `Page.layout = { title: '...', description: '...' }`. Use when the page uses the default layout resolver (AppLayout for non-auth non-settings, AuthLayout for auth, `[AppLayout, SettingsLayout]` for settings) and only needs to pass static props. Inertia merges these into the resolver's layout component. Used by auth pages, settings pages, dashboard, and submissions pages.

2. **Callback layout** — `Page.layout = (page: ReactElement) => <AppLayout breadcrumbs={[...]}>{page}</AppLayout>`. Use when the page needs a non-default wrapper (PublicLayout for public marketing pages, or an explicit AppLayout with specific breadcrumbs). The callback must return a new JSX element, not the bare `page` — see the ❌ pattern above. Used by `about.tsx`, `home.tsx`, `contact.tsx`, `contact-thank-you.tsx`, `inbox/index.tsx`, `thread-studio.tsx`.

3. **`setLayoutProps`** — `setLayoutProps({ title: '...' })`. Use from inside a page component when the layout title or other props depend on runtime state. Used by `two-factor-challenge.tsx`. Prefer static layout props or callback layouts when the values are known at module level.

**`satisfies AppLayoutPageProps`** on static object layouts is encouraged but not mandatory. Package-owned pages (`SubmissionsIndex`, `SubmissionsShow`) use it; starter-owned pages do not yet. New starter-owned pages may adopt it, but existing pages should not be converted purely for the type annotation.

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
php artisan route:clear
php artisan wayfinder:generate --with-form --no-interaction
php artisan evolayer:ontology:compile --no-erd --no-interaction
```

`composer setup` and `post-create-project-cmd` do this for end users; CI does it explicitly in the tests workflow. If you add a workflow, repeat that recipe before `npm run types:check` or `npm run build`.

## Patch policy

The only committed patch is `patches/laravel-ai-structured-streaming.patch`, applied automatically via `cweagans/composer-patches` (`extra.patches` in `composer.json`). It enables structured-output streaming until upstream `laravel/ai` ships the fix. See `patches/README.md` for the verification matrix and revisit conditions. Don't add new vendor patches without a similar dossier.

## Verification suite

Run before opening a PR:

```bash
composer validate --strict
composer test                      # Pest Feature + Unit
php artisan evolayer:doctor        # package's health check (informational; CI enforces strictness)
npm run types:check                # tsc --noEmit
composer lint:check                # Pint
npm run lint:check                 # ESLint
npm run format:check               # Prettier (resources/ only)
npm run build                      # Vite client + SSR
```

All eight gates green on HEAD before push. The public starter CI runs the same suite on push, pull request, and `workflow_dispatch` (see RELEASE.md).

### Verification categories

Agents must distinguish between three verification categories:

1. **Static verification** — type-check, lint, format, build. Fully automated; `npm run types:check && npm run lint:check && npm run format:check && npm run build` covers this.
2. **Test-suite verification** — Pest Feature/Unit tests, `php artisan evolayer:doctor`. Automated CI gates. `composer test` covers this. The resync-safety sentinel test (`ResyncSafetyTest`) is a test-suite gate, not a manual check.
3. **Browser/manual runtime smoke** — loading pages, clicking UI elements, keyboard shortcuts. These cannot be verified by HTTP/string tests. No agent may claim a browser smoke passed without actually running it in a browser. See the runtime smoke checklist in RELEASE.md for the required manual checks before tagging a release.

## Dev server handoff

For one-off verification, stop `npm run dev` before handing back. Only leave Vite
running when the user explicitly wants a live browser session, and then report
the working directory, command, configured port, and process/session you left
behind.

When `VITE_DEV_SERVER_PORT` is set for a hosted local workflow, keep that value
aligned with the Nginx `proxy_pass` port. Check the listener with
`ss -ltnp | grep ':<port> '`. A 502 from `/@vite/`, `/@react-refresh`, or
`/resources/` usually means Vite is stopped, bound to a different port, or the
host proxy was not updated/reloaded. Host-level Nginx files and
client-specific domains remain downstream concerns, not starter changes.

If `VITE_DEV_SERVER_ORIGIN` is also set, it must match the browser-visible app
origin and the vhost must proxy `/vite-hmr` to the same Vite port. Do not add a
client's concrete hostname to this starter; document it in that downstream app.

## Out of scope — do not invent

- New starter-local example routes, pages, AI agents, or blocks (those belong in the package).
- Provider-platform expansions (model sweeps, billing, cost estimation, stale-reprobe workflows). The package's `evolayer:ai:*` commands cover the minimum probe surface; keep starter docs to the one smoke command and link out.
- Sibling EvoLayer layers (Commerce / SaaS / RLS). Those ship as separate packages with their own starter repos.
- Hub / `evodevops.com` editorial work. That lives off-repo.
- Rewriting `welcome.tsx`. It's inherited Laravel-kit fallback; see the header comment in that file.

## Links

- [`README.md`](README.md) — full user-facing story.
- [`CONTRIBUTING.md`](CONTRIBUTING.md) — full routing matrix + flag conventions + PR checklist.
- [`RELEASE.md`](RELEASE.md) — release checklist, package resolution, and public CI posture.
- [`CHANGELOG.md`](CHANGELOG.md) — `[Unreleased]` covers everything between the EvoLayer pivot and the next tag.
- [`SECURITY.md`](SECURITY.md), [`SUPPORT.md`](SUPPORT.md) — community policies.
- [`patches/README.md`](patches/README.md) — vendor patch dossier.

<!--
  ──────────────────────────────────────────────────────────────────
  Boost-generated framework guidelines follow below.

  Boost rewrites the HTML-tag-delimited block at the bottom of this
  file in place on every `php artisan boost:install` / `boost:update`
  (see vendor/laravel/boost/src/Install/GuidelineWriter.php). Any
  rules placed inside that block are silently wiped on the next run.

  Project-specific rules MUST live above this comment, outside the
  block. When project-specific and Boost-generated guidance disagree,
  project-specific wins.

  Important: do not put the literal opening or closing marker tags
  anywhere in project-specific prose, even inside backticks — Boost's
  regex (preg_replace, limit 1) does not respect markdown code spans
  and will treat the first occurrence as the start of the block.
  ──────────────────────────────────────────────────────────────────
-->

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/ai (AI) - v0
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
