# Agents Guide — EvoLayer Base Starter

For AI coding agents (Claude Code, Codex, OpenCode, Cursor, Aider, …) and any automation operating on this repo. Humans should start with [`README.md`](README.md) and [`CONTRIBUTING.md`](CONTRIBUTING.md).

This file is the short, prescriptive version of those documents tuned for agent decision-making. When in doubt about an architectural rule, the routing matrix in [`CONTRIBUTING.md`](CONTRIBUTING.md) is the source of truth.

**Read order.** The project-specific guidance below is the authoritative section for *this* starter — package/starter boundaries, feature-flag conventions, patch policy, and out-of-scope rules. Generic Laravel / Inertia / React / Wayfinder / Pint guidelines from [Laravel Boost](https://laravel.com/docs/boost) follow in the second half of the file, in an auto-regenerated block at the bottom. When the two sections disagree, the project-specific guidance wins — Boost's framework rules are background, not foreground. The Boost-generated block is rewritten in place by `php artisan boost:update`; never edit content inside it (rules placed there are silently wiped on the next run). This file is mirrored byte-identically to `CLAUDE.md` so agents that look for either filename find the same content.

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

## Inertia layout resolver

`resources/js/app.tsx` registers a `createInertiaApp` layout resolver that defaults to `AppLayout` (sidebar shell) for any page outside `auth/` and `settings/`, and `null` for `welcome` (the inherited Laravel-kit fallback). Per-page layouts override the resolver, but **the layout function must return a new JSX element**:

- ✅ `Page.layout = (page: ReactElement) => <>{page}</>;` — pages with their own full-page chrome (marketing / landing).
- ✅ `Page.layout = (page: ReactElement) => <PublicLayout>{page}</PublicLayout>;` — pages using the shared public shell at `resources/js/layouts/public-layout.tsx`.
- ❌ `Page.layout = (page) => page;` — Inertia does not recognise the bare ReactElement as a render function, falls back to the resolver, and re-wraps the page in `AppLayout` (sidebar visible on public pages).

Use `|` as the title separator, not `-`. The resolver sets it via `title: (title) => (title ? \`${title} | ${appName}\` : appName)`.

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

=== phpunit/core rules ===

# PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should cover all happy paths, failure paths, and edge cases.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

## Running Tests

- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
