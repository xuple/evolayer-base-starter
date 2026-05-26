# Contributing

Thanks for helping improve EvoLayer Base Starter.

The starter is the free public `composer create-project` entry point for EvoLayer Base. It intentionally ships in kitchen-sink posture so developers can see the full demo surface immediately, while the package itself remains opt-in by default.

## Ground Rules

- Do not commit secrets, `.env`, `auth.json`, credentials, API keys, generated private keys, or local Composer auth.
- Keep pre-release private repository access explicit. Do not commit machine-local path repositories.
- Preserve the public identity: starter `xuple/evolayer-base-starter`, package `xuple/evolayer-base`, route names `evolayer.base.*`.
- Keep `composer.lock` uncommitted so `composer create-project` resolves the package fresh for each new app.
- Prefer small, reviewable PRs with tests or a clear explanation of why tests are not applicable.

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

Update README, CHANGELOG, or RELEASE when changing install flow, route/page IA, feature flags, CI behavior, package resolution, commands, or public release posture.

## Pull Request Checklist

- Tests or verification notes included.
- Public docs updated where needed.
- `composer.lock` remains uncommitted.
- No local path repository or private credential committed.
- No stale `EvoDevOps Base` or old `evodevops/base` package naming introduced.
