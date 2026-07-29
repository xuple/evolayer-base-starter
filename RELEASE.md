# Releasing EvoLayer Base Starter

The starter (`xuple/evolayer-base-starter`) ships alongside the package
(`xuple/evolayer-base`). The authoritative release flow lives in the **package's
`RELEASE.md`**; this is the starter-specific summary. Base and the starter are
free/public MIT projects on GitHub and Packagist. The current public release line
is `xuple/evolayer-base-starter v0.2.0-rc.2` consuming the exact-pinned
`xuple/evolayer-base v0.2.0-rc.2`. GitHub is the public publication source; the
self-hosted Forge remains an internal mirror.

Web IA is part of the release posture: `evodevops.com` teaches and markets the
family, `evodevops.com/evolayer-base/docs` is the canonical Base documentation root, `/`
mounts the EvoLayer Base demo/install explainer, and the package's opt-in
marketing routes expose that same page at `/about`.

## Current public release

Current starter release: **v0.2.0-rc.2**. It exact-pins
**xuple/evolayer-base v0.2.0-rc.2**. Future fixes use new releases; do not move
published tags.

## Prerelease history (0.2.0 train)

No public Starter `v0.2.0-rc.1` tag, GitHub release, or Packagist package version
was published. Starter `v0.2.0-rc.2` was published as the validated counterpart
to Base `v0.2.0-rc.2`.

Matching the RC.2 ordinal was a release-specific decision for this tested pair,
not a permanent lockstep-versioning policy.

## 0.2.0-rc.2 public release

The published Starter `v0.2.0-rc.2` exact-pins the public
`xuple/evolayer-base:v0.2.0-rc.2` ZIP distribution:

| Evidence | Bound value |
| --- | --- |
| Starter release | `v0.2.0-rc.2` |
| Starter annotated tag object | `c1a272fd50ddb58b92576d5f2d3249ee48727ee5` |
| Starter tagged commit | `1fc8dad135a91c4a084ea24c05965806bd05699a` |
| Starter release tree | `2d4490afbd6cac0b1a928ddb32f78bd71fac7b5b` |
| Base dependency | `xuple/evolayer-base:v0.2.0-rc.2` |
| Base annotated tag object | `2d59b03b053dbe80e2347c198fcc41bacb319e87` |
| Base commit/source and dist reference | `73f30df35a1a416d07e65044257c7c1a11ce9455` |
| Base release tree | `760f228b6f4d8f45ab4ce9cba52216fc84557d1a` |

Do not confuse the two: the Starter release version and the Base dependency
version share the `v0.2.0-rc.2` ordinal under the policy above, but they are
separate artefacts with separate references.

Release-step status:

```text
candidate prepared                      — done
human browser runtime smoke             — passed 2026-07-28 (see checklist item 5)
Starter tag v0.2.0-rc.2 published       — complete
Packagist source/dist verification      — complete
fresh public create-project proof       — complete
public social-preview verification      — outstanding (no suitable public URL)
```

The distribution manifest must be refreshed from those installed package bytes
and pass its check-only mode before the Starter candidate is reviewed or tagged:

```bash
composer evolayer:manifest:refresh
composer evolayer:manifest:check
```

No path repository, local package definition, version alias, sibling checkout,
or `file://` archive belongs in the release candidate or its lock.

### Generated-application profile lifecycle

Fresh application installations select posture before migrations and seeding:

```bash
EVOLAYER_BASE_INSTALL_PROFILE=application \
  composer create-project xuple/evolayer-base-starter app
cd app
npm run profile:prepare
php artisan evolayer:profile:verify --json
```

The supported sequence is:

```text
select application posture
→ migrate and seed
→ prepare generated contracts and reviewed frontend gates
→ verify without mutating managed source or profile intent
```

A normal fresh application install does not require `migrate:fresh`. Application
posture disables public registration and prevents the known demonstration
account from being seeded. When an existing demo installation is transitioned,
an existing `test@example.com` account is preserved and reported for manual
resolution; the Starter does not infer ownership or delete application data from
weak identity heuristics.

`npm run profile:prepare` clears configuration and route caches in fresh
processes, regenerates Wayfinder and ontology output, runs the reviewed frontend
gates, and writes ignored local preparation evidence bound to current inputs and
outputs. That preparation receipt is non-authoritative workflow evidence.
`evolayer:profile:verify` is non-mutating and delegates stable capability results
to Base, which records bounded verification state.

The supported legacy proof begins with exact Starter `v0.1.19` and Base `v0.1.9`.
It updates through public Composer metadata, adopts only recognised pristine
legacy provenance, explicitly selects application posture, preserves and reports
any legacy demo-user conflict, reconciles Starter-owned source, prepares, and
verifies. Modified, ejected, malformed, unknown, stale, missing, or unreadable
inputs fail closed.

### Private contact attachment storage

Contact attachments are evidence and must use private storage. New Starter
installations set `MEDIA_DISK=local`, independently of the disk used for ordinary
public assets. The `local` disk stores attachment bytes under
`storage/app/private`; production deployments must place that path on persistent,
appropriately backed-up storage when attachment retention is required.

Attachment links are served through the Starter's authenticated, verified-admin
route rather than through `/storage` or another public static-file path. Existing
deployments must set `MEDIA_DISK` to a private disk explicitly. Changing the
default does not move existing Media Library files or rewrite their recorded disk;
review and migrate any legacy records stored on a public disk as a separate,
operator-controlled data operation.

## 0.2 RC dependency-audit boundary

Production dependencies audit cleanly:

```bash
npm audit --omit=dev --audit-level=high
```

The full audit still reports exactly seven high-severity findings in the
development-only chain comprising `@eslint/config-array`, `@eslint/eslintrc`,
`brace-expansion`, `eslint`, `eslint-plugin-import`, `eslint-plugin-react`, and
`minimatch`. The reviewed lint command supplies repository-owned paths and does
not expose this toolchain to application requests or production input.

ESLint `10.8.0` is available, and current `@eslint/js` and
`typescript-eslint` releases admit ESLint 10. As verified on 2026-07-29, the
stable `eslint-plugin-import@2.32.0` peer range admits ESLint only through 9,
while the stable `eslint-plugin-react@7.37.5` range admits it only through
`^9.7`. The compatibility preflight therefore stopped without installing an
invalid peer graph. No forced install, `--legacy-peer-deps`, override, plugin
downgrade, or plugin removal was accepted.

This is a temporary compatibility deferral, not remediation. The reviewed
exception expires on 2026-11-30, and CI fails from 2026-12-01 if no supported
remediation has landed. `npm run audit:full` continues to fail when a compatible
fix appears, the advisory or package scope changes, severity escalates to
critical, or any unrecognised high finding appears. Low- and moderate-severity
findings remain visible in npm's full report but do not block this bounded
exception check.

## Current unreleased dependency updates

The current unreleased state includes the seven reviewed PHP dependency
updates, the Inertia/Fontsource/Radix frontend updates, and the revalidated npm
audit exception described above. It does not change the exact Base
`v0.2.0-rc.2` pin, application source, or any EvoLayer-managed surface, and the
non-mutating resync inspection proposed no changes. No release version has been
assigned to these updates.

## create-project flow (end users)

```bash
composer create-project xuple/evolayer-base-starter my-app
cd my-app && npm install && npm run build && php artisan serve
```

`npm run build` is the default frontend quality gate and runs both the client
bundle and the SSR bundle.

`post-create-project-cmd` runs `key:generate`, creates the SQLite database,
`migrate --seed`, clears stale route caches, `wayfinder:generate`, and
`evolayer:ontology:compile`. Demo admin: `test@example.com` / `password`.

## Route identity migration notes

The starter treats `/home` (host-owned route named `home`) as the canonical
authenticated entry point. Public `/` is named `welcome` and is the
logout/public landing route rendering the `evolayer/base` explainer. When
changing this contract in a downstream app, review:

- `config/fortify.php` `home`.
- Shell logo links, passkey success fallbacks, and any hard-coded `/dashboard`
  assumptions.
- Generated Wayfinder imports after route changes. Run `php artisan route:clear`
  before `php artisan wayfinder:generate --with-form --no-interaction`.

## How the package is resolved

The starter consumes the package from **Packagist** as a tagged release:

```jsonc
"require": { "xuple/evolayer-base": "0.2.0-rc.2" }
```

No custom `repositories` entry ships in the public starter — Composer resolves
`xuple/evolayer-base` straight from Packagist, exact-pinned while `0.x`.
`composer.lock` **is committed**: the starter is a tested, reproducible
distribution and `composer create-project` installs the locked graph (Composer
honors a committed lock). Bump the framework deliberately via a release PR
rather than letting installs drift.

Created applications likewise commit their generated `composer.lock` so
deployments resolve the same dependency graph that was tested.

> **Pre-public staging (historical).** Before `xuple/evolayer-base` was
> published to Packagist, the starter resolved it from a private Forge `vcs`
> repository at `dev-main` (`ssh://git@<private-forge-host>/<owner>/evolayer-base.git`).
> That `repositories` block was removed when the starter moved to Packagist distribution; it is
> retained here only as internal context.

**Local side-by-side package dev (optional):** to edit the package locally and
have this starter pick it up without pushing to the forge, add an _uncommitted_
path override:

```bash
composer config repositories.evolayer-base path ../evolayer-base   # do not commit
```

## Patch-release checklist

1. Full verification suite green: `composer test`, `composer validate --strict`, `php artisan evolayer:doctor --strict --no-ansi`, `npm run types:check`, `npm run build` (client + SSR), `composer lint:check`, `npm run lint:check`, `npm run format:check`.
2. The exact-pinned dependency resolves the published package from Packagist — `composer validate --strict` must stay clean.
3. A clean Packagist `composer create-project xuple/evolayer-base-starter <dir>` (no `--repository` / `--stability` flags) succeeds end to end.
4. **Live AI** — add provider keys to `.env`, then run the relevant smoke
   commands. ThreadStudio's runtime-approved providers are **Gemini** (default) and
   **OpenAI** — both are selectable as `AI_THREAD_STUDIO_PROVIDER` and pass
   structured streaming. **Anthropic** is diagnostic-eligible but blocked for
   ThreadStudio runtime / pending re-verification: its non-streaming
   `php artisan evolayer:ai:smoke-test anthropic` passes, but
   `php artisan evolayer:ai:stream-check anthropic` currently returns zero
   `TextDelta` events and an empty final payload, so it is not runtime-approved
   for ThreadStudio runtime selection. **NVIDIA / OpenCode / OpenRouter** remain
   router-backed diagnostic-eligible probe candidates, not runtime-approved
   ThreadStudio providers.
5. **Manual runtime smoke** — the following checks exercise browser behaviour
   that HTTP/string tests cannot verify. A human must perform them before
   tagging a release; no agent may claim these passed without actually running
   them in a browser.
    - Public `/` (route `welcome`) loads the EvoLayer Base explainer (`evolayer/base`).
    - Login/register path renders Fortify auth forms.

- Authenticated `/home` loads the Home launcher with command bar visible.
    - Home page command bar click opens the command palette (the default `AppLayout` → `app-sidebar-layout` header has no search-icon opener; the header search button in `resources/js/components/app-header.tsx` only belongs to the unused `app-header-layout` variant, which no routed page currently selects).
    - Home page command bar opens the command palette from a mobile viewport.
    - `Ctrl`/`Cmd`+`K` opens the command palette via keyboard shortcut.
    - Settings navigation reaches Profile, Security, and Appearance tabs.
    - Logout redirects back to the public landing page.
    - `/dashboard` loads (retained scaffold route, not the primary post-auth destination).

    **`v0.2.0-rc.2` status — Human browser smoke: PASSED.**

    | Field | Value |
    | --- | --- |
    | Tester | Edward Yeboah |
    | Date | 2026-07-28 |
    | Browser | Chrome 150.0.7871.182 (64-bit) on Windows 11 x64; spot-checked in Brave |
    | Runtime host | Ubuntu 24.04.2 LTS x64 headless, kernel `6.8.0-136-generic` |
    | Runtime commit | `9d03af75f9cfbf26f22f9503e3826ba4a83387f3` |
    | Runtime tree | `6eb5f49af1bca1c671fc6d38efda1aa1864eb828` |

    Every documented checklist item passed. `Cmd+K` was not applicable on
    Windows 11, which intercepts `Win+K`; `Ctrl+K` passed and exercises the same
    `metaKey || ctrlKey` handler. No release-attributable errors or warnings were
    observed in a clean Chrome Incognito profile with extensions disabled.

    Environment caveats are recorded with the attestation: the smoke ran against
    built assets served by `php artisan serve` rather than a Vite dev server,
    under `EVOLAYER_BASE_PROFILE=demo`, over a plain-HTTP LAN origin, and with an
    unrelated Inertia SSR renderer reachable on the configured SSR port. That
    renderer was disclosed and assessed immaterial on recorded evidence: it
    differs from the tested runtime only in the three command-palette source
    files, and the palette emits no server-rendered markup.

    This human browser smoke applies to the published `v0.2.0-rc.2` runtime
    only. The current unreleased state changes Inertia and Radix
    frontend dependencies, so a new human browser smoke is required against the
    final upgraded runtime before any future tag.

6. **Manual social preview smoke** — run on a staging/production URL with real
   `APP_URL` / optional `SITE_URL` values. Verify at least Slack, Teams,
   Discord, LinkedIn Post Inspector, Meta Sharing Debugger, WhatsApp, and
   iMessage / Apple Messages when available. Check Google URL Inspection and
   favicon recrawl where applicable. Do not claim platform preview support from
   local HTTP tests alone; live unfurlers cache and crawl differently.

    **Classification: post-publication verification, required before the
    release is declared complete — not a pre-tag gate.** Unlike item 5, this
    step carries no "before tagging" requirement, and it depends on a deployed
    public URL that does not exist until the release is published and hosted.
    This records the existing strictness rather than relaxing it: the release
    is not complete until the check is performed with real evidence, and a
    missing public URL is reported as an outstanding verification rather than
    treated as a pass.

    **`v0.2.0-rc.2` status — pending; no suitable public URL is available.**

7. Move the relevant `CHANGELOG.md` `[Unreleased]` entries into the new patch
   version immediately before tagging.

The first-hour install path has been rehearsed end-to-end from a clean
directory before tagging: `composer create-project` must resolve the current
starter release and its exact-pinned base package, apply the `laravel/ai` patch,
migrate/seed, generate Wayfinder and ontology, and then pass `npm install`,
`npm audit`, `npm run build`, `evolayer:doctor --strict`, and `composer test`.

## Distribution

`xuple/evolayer-base` and `xuple/evolayer-base-starter` are **public on GitHub
and published on Packagist**. The published Starter `v0.2.0-rc.2` consumes Base
`v0.2.0-rc.2`, exact-pinned while `0.x`, from Packagist. **GitHub is the public
publication source**; the self-hosted Forge remains an internal mirror
(`origin`). See the package `RELEASE.md` for the package-first release flow.

## CI access note

Public CI runs on push, pull request, and `workflow_dispatch`. It no longer
needs a repository-access secret: `composer install` resolves the base package
from Packagist, then the workflows run validation, audits, generated frontend
contract preparation, build, strict doctor, tests, lint, and format checks.

## Open decisions

The base and starter packages are tagged, public, Packagist-visible, and covered
by public CI. Remaining launch polish: the minimum `evodevops.com/evolayer-base/docs`
page and external announcement copy. (Starter-side social-preview metadata and
the default OG image now ship in-repo.) The ThreadStudio provider roster is settled
(Gemini + OpenAI runtime-approved;
Anthropic diagnostic-eligible but blocked for ThreadStudio runtime / pending
re-verification; routers are router-backed diagnostic-eligible probe
candidates); what remains open there is Anthropic's structured-streaming
investigation. Remotes: `origin` is the internal Forge mirror; `github`
(`git@github.com:xuple/evolayer-base-starter.git`) is the public publication
source.
