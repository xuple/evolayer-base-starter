# The EvoLayer Base promise

This document is the contract between EvoLayer Base and the developers who build
on it. It exists so there are no surprises about what you own, what the framework
manages, how updates work, and what is free. If anything below stops being true,
that is a bug in the promise — open an issue.

## What EvoLayer Base is

**EvoLayer Base is a fully working AI application layer, delivered through a
project template.**

The install package is named `xuple/evolayer-base-starter` for historical and
Packagist convenience, but the product contract is **framework + project
template**.

- **`xuple/evolayer-base`** — the framework package. Owns the AI runtime,
  ontology, `evolayer:*` commands, and managed example surfaces and blocks.
- **`xuple/evolayer-base-starter`** — the project template. A tested,
  reproducible distribution you `composer create-project` into your own app.

## What you own, what the framework manages

EvoLayer gives you the best of both worlds: a clean, manageable starting point 
with continuous upstream updates, and the freedom to take total control exactly when you need it.

| Surface | Owner | Lifecycle |
| --- | --- | --- |
| **Substrate** (AI runtime, ontology compiler, `evolayer:*` commands) | **Framework** | Updated by upgrading the `xuple/evolayer-base` package. |
| **Generates** (Wayfinder routes, compiled ontology TS types) | **Generated** | Deterministically rebuilt by CLI commands. Not hand-edited. |
| **Managed Surfaces** (Examples, Demo workflows, Optional blocks) | **Framework (until ejected)** | Updated safely by `php artisan evolayer:resync` as long as they are pristine. |
| **App code** (Your routes, pages, logic, configuration, ejected examples) | **You (the App)** | Never overwritten by the framework. |

For the strict technical definitions, see the canonical **[EvoLayer Framework Contract](https://github.com/xuple/evolayer-base/blob/main/docs/contract.md)**.

## Reproducibility

The project template **commits `composer.lock`** and ships a tested dependency
graph. `composer create-project` installs that locked graph rather than
re-resolving it, so two installs of the same template version are identical.
`xuple/evolayer-base` is exact-pinned while it is `0.x`; the framework is bumped
through deliberate release PRs, not by silent install-time drift. Your generated
app commits its own `composer.lock` too, for the same reason. (See
[`docs/migration/create-project-lock-behavior.md`](docs/migration/create-project-lock-behavior.md).)

## Updates, ownership, and the exit

EvoLayer acts as a partner in your codebase. We handle the complex AI plumbing 
and keep your example surfaces updated, so you can focus on building your actual product. 

- **The application layer manages** the heavy lifting safely out of sight. You receive 
  framework updates for the underlying AI logic and example surfaces automatically.
- **Ejecting empowers you.** Whenever you need to deeply customize a managed 
  example or block, simply run `php artisan evolayer:eject <surface>`. 
  You take full ownership of the code, and we'll step out of the way (ceasing updates for that specific surface).
- **Scope:** eject applies to examples, demo workflows, and optional blocks — not
  to the AI runtime, ontology compiler, provider contracts, or core commands,
  which remain framework-managed.

You always maintain complete creative control. You get continuous improvements 
out-of-the-box, with a built-in release valve the second you want to write the rules yourself.

## Licensing

**Today, everything that ships is MIT-licensed** — the framework package, the
project template, and the bundled example surfaces and blocks.

We plan to offer **premium blocks or surfaces** as separately licensed
packages. When that happens, the boundary will be stated explicitly here
and in package metadata before anything ceases to be MIT: what is free will
be named, and what is paid will be named.

## Summary

- It is a **working AI application layer delivered via a project template**.
- **You own** your app code, configuration, and branding. The **application layer
  manages** runtime, examples, and upgradeable surfaces.
- Installs are **reproducible** (committed lock, exact pin while `0.x`).
- **Ejecting** safely hands the keys over to you for any managed example.
- **MIT today**, with any future premium boundary disclosed in advance.
