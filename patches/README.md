# Vendor patches

Applied automatically by `cweagans/composer-patches` on every `composer install` / `composer update`. Declared under `extra.patches` in `composer.json`.

## `laravel-ai-structured-streaming.patch`

**Target:** `laravel/ai` v0.8.1 (the version pinned in `composer.lock`) — `src/Providers/Concerns/StreamsText.php`. Written against v0.6.5; the hunks apply unchanged to v0.8.x and v0.9.x and **fail from v0.10.0** (see Status below).

**What it does:** Removes the hard guard that prevented `agent->stream()` on agents implementing `HasStructuredOutput`, and forwards the agent's schema through to `streamText()`. The guard threw:

```
InvalidArgumentException: Streaming structured output is not currently supported.
```

`streamText()` already accepted a `?array $schema` parameter — the SDK was passing `null` and rejecting structured agents up front. The patch is two functional changes:

1. Replace the guard with `$schema = $agent instanceof HasStructuredOutput ? $agent->schema(new JsonSchemaTypeFactory) : null;`
2. Pass `$schema` instead of `null` to `$this->textGateway()->streamText(...)`.

**Why we need it:** ThreadStudio's `streamCompose()` path emits real token-level `field_delta` / `field_complete` SSE events parsed from a streaming JSON object. Without the patch, structured-output agents fall back to a non-streaming round trip, and the UI has to fake progressive disclosure with a typewriter timer.

**Verification:** Live-tested end-to-end (recorded when the patch was written, against v0.6.5) where provider streaming currently works via `php artisan evolayer:ai:stream-check {provider}`:

| Provider | First token | Total  | TextDelta events | All 6 fields |
| -------- | ----------- | ------ | ---------------- | ------------ |
| Gemini   | ~3 s        | ~4 s   | ~9 (batched)     | ✅           |
| OpenAI   | ~2 s        | ~5 s   | ~200+ (granular) | ✅           |

Anthropic structured output passes the non-streaming smoke path, but structured
streaming currently returns zero `TextDelta` events and an empty final payload
from `php artisan evolayer:ai:stream-check anthropic`. The package command tests
cover that failure mode so it cannot be mistaken for a green structured-streaming
provider.

## Status — checked 2026-10-02 against upstream `laravel/ai`

**Still required.** The guard is present, unchanged, in every tagged release from v0.8.1 through v1.0.1 (2026-09-30), and v1.0.1 ships no alternative structured-streaming API — `agent->stream()` on a `HasStructuredOutput` agent still throws. The package's `ThreadStudioComposer::streamCompose()` streams `ThreadStudioAgent`, which implements `HasStructuredOutput`, so the starter cannot drop the patch until upstream lands the fix. This check compared source only; it did not re-run the live provider smoke.

| `laravel/ai`             | Patch applies? | Notes                                                                                                                  |
| ------------------------ | -------------- | ---------------------------------------------------------------------------------------------------------------------- |
| v0.8.1 (locked) – v0.9.1 | ✅             | `StreamsText.php` differs from v0.8.1 by one line                                                                      |
| v0.10.0 – v0.11.2        | ❌             | `StreamsText.php` rewritten (+58/−11 by v0.10.0); the hunks no longer match                                            |
| v1.0.0 – v1.0.1          | ❌             | Rewritten further (+119/−41 vs v0.8.1); 1.x also requires `laravel/mcp` ≥ 1.0 and the 1.0 conversation-table migration |

**Drift protection:** `xuple/evolayer-base` requires `laravel/ai` `^0.8.1` (i.e. `<0.9.0`) and the starter commits an exact lock, so `composer update` cannot move onto a version the patch does not fit. The first Base release that lifts that cap must re-author this patch against the new `StreamsText.php` — or land the fix upstream first. Expect the patches plugin to report `FAILED to patch` at that point.

## Upstream PR — not yet filed

The fix belongs upstream in `laravel/ai`. Filing was deferred when the patch was written (v0.6.5) on the expectation that a fast-moving SDK would ship a parallel design. That has not happened: three minor releases and the 1.0 line have landed with the guard intact. **Filing the PR now is recommended**, rebased onto the 1.x `StreamsText.php`, before the next `laravel/ai` bump forces the patch to be rewritten anyway. Reference the `ThreadStudioStreamTest` suite in this repo and the `evolayer:ai:stream-check` command as verification evidence.

**Where to file it:** https://github.com/laravel/ai

**When to revisit:** on every `laravel/ai` bump, and whenever Base lifts its `^0.8.1` cap:

1. Run `composer update laravel/ai` and watch for the patches plugin reporting `FAILED to patch`. From v0.10.0 this is expected and does **not** by itself mean upstream shipped the fix — check the new `StreamsText.php` for the guard string `Streaming structured output is not currently supported.` first.
2. If the guard is gone, re-run `php artisan evolayer:ai:stream-check gemini` and `openai` against the unpatched vendor copy. Also re-check Anthropic once its structured-streaming path emits `TextDelta` events. If runtime-approved providers pass without the patch, delete the patch file, the `extra.patches` entry in `composer.json`, and run `composer patches-relock` so the tracked `patches.lock.json` matches.
3. If the guard is still there, re-author the patch against the new file, verify with the same smoke commands, and update the **Target** line and the table above.
