# The EvoLayer Base promise

This document is the contract between EvoLayer Base and the developers who build
on it. It exists so there are no surprises about what you own, what the framework
manages, how updates work, and what is free. If anything below stops being true,
that is a bug in the promise — open an issue.

> **Draft.** Wording and commercial specifics are under review. The shape of the
> contract (identity, ownership, reproducibility, eject, licensing) is settled;
> the exact phrasing is not yet final.

## What EvoLayer Base is

**EvoLayer Base is a Laravel + React application framework, delivered through a
project template.** It is *not* a conventional starter kit where every file
becomes yours to edit the moment you scaffold.

The install package is named `xuple/evolayer-base-starter` for historical and
Packagist convenience, but the product contract is **framework + project
template** — not "starter kit." We avoid "starter kit," "fully yours after
create-project," and "edit anything," because they would misdescribe how the
managed surfaces actually work.

- **`xuple/evolayer-base`** — the framework package. Owns the AI runtime,
  ontology, `evolayer:*` commands, and managed example surfaces and blocks.
- **`xuple/evolayer-base-starter`** — the project template. A tested,
  reproducible distribution you `composer create-project` into your own app.

## What you own, what the framework manages

For the strict definitions of what surfaces the framework manages, what you own, and how the `resync` and `eject` commands operate, see the canonical **[EvoLayer Framework Contract](https://github.com/xuple/evolayer-base/blob/main/docs/contract.md)**.

## Reproducibility

The project template **commits `composer.lock`** and ships a tested dependency
graph. `composer create-project` installs that locked graph rather than
re-resolving it, so two installs of the same template version are identical.
`xuple/evolayer-base` is exact-pinned while it is `0.x`; the framework is bumped
through deliberate release PRs, not by silent install-time drift. Your generated
app commits its own `composer.lock` too, for the same reason. (See
[`docs/migration/create-project-lock-behavior.md`](docs/migration/create-project-lock-behavior.md).)

## Updates, lock-in, and the exit

Because EvoLayer Base manages real surfaces, it creates an ongoing relationship a
plain starter kit does not: you receive framework updates, and in exchange the
managed surfaces are not arbitrary copies for you to fork freely. We think that
is a fair trade — but only if the exit is honest:

- **Eject is the exit.** `php artisan evolayer:eject <surface>` copies a managed
  example or block into your application's ownership.
- **What you gain:** full control of that surface — edit it however you like.
- **What you give up:** managed updates for it. Once ejected, that surface no
  longer receives framework changes; it is yours to maintain.
- **Scope:** eject applies to examples, demo workflows, and optional blocks — not
  to the AI runtime, ontology compiler, provider contracts, or core commands,
  which remain framework-managed.

You are never trapped in a managed surface you dislike; you can always own it.
You just can't have both "I edited it freely" and "it keeps auto-updating."

## Licensing

**Today, everything that ships is MIT-licensed** — the framework package, the
project template, and the bundled example surfaces and blocks.

We may later offer **premium blocks or surfaces** as separately licensed
packages. If and when that happens, the boundary will be stated explicitly here
and in package metadata before anything ceases to be MIT. "Framework" will never
be a euphemism for hidden paywalls: what is free will be named, and what is paid
will be named.

## Summary

- It's a **framework delivered via a project template**, not a starter kit you
  wholly own.
- **You own** your app code, configuration, and branding. The **framework
  manages** runtime, examples, and upgradeable surfaces.
- Installs are **reproducible** (committed lock, exact pin while `0.x`).
- **Eject** is the honest exit from any managed example — at the cost of its
  updates.
- **MIT today**, with any future premium boundary disclosed in advance.
