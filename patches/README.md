# Vendor patches

Applied automatically by `cweagans/composer-patches` on every `composer install` / `composer update`. Declared under `extra.patches` in `composer.json`.

## `laravel-ai-structured-streaming.patch`

**Target:** `laravel/ai` v0.6.5 — `src/Providers/Concerns/StreamsText.php`

**What it does:** Removes the hard guard that prevented `agent->stream()` on agents implementing `HasStructuredOutput`, and forwards the agent's schema through to `streamText()`. The guard threw:

```
InvalidArgumentException: Streaming structured output is not currently supported.
```

`streamText()` already accepted a `?array $schema` parameter — the SDK was passing `null` and rejecting structured agents up front. The patch is two functional changes:

1. Replace the guard with `$schema = $agent instanceof HasStructuredOutput ? $agent->schema(new JsonSchemaTypeFactory) : null;`
2. Pass `$schema` instead of `null` to `$this->textGateway()->streamText(...)`.

**Why we need it:** ThreadStudio's `streamCompose()` path emits real token-level `field_delta` / `field_complete` SSE events parsed from a streaming JSON object. Without the patch, structured-output agents fall back to a non-streaming round trip, and the UI has to fake progressive disclosure with a typewriter timer.

**Verification:** Live-tested end-to-end on both providers via `php artisan ai:stream-smoke {provider}`:

| Provider | First token | Total  | TextDelta events | All 6 fields |
| -------- | ----------- | ------ | ---------------- | ------------ |
| Gemini   | ~3 s        | ~4 s   | ~9 (batched)     | ✅           |
| OpenAI   | ~2 s        | ~5 s   | ~200+ (granular) | ✅           |

Anthropic verification deferred until credits are configured.

## Upstream PR — deferred

The fix belongs upstream in `laravel/ai`. We deferred filing it because:

- It's a tiny two-line change in a fast-moving SDK; upstream may already have a parallel design in flight.
- The repo's correctness is fully covered locally by the patch and our 22-test `ThreadStudioStreamTest` suite.
- Pursuing it costs more than the value of removing the patch in the short term.

**When to revisit:** When a new `laravel/ai` minor release lands (anything past v0.6.5). At that point:

1. Run `composer update laravel/ai` and watch for the patches plugin reporting `FAILED to patch` — that means upstream changed `StreamsText.php` and may have shipped the fix.
2. Re-run `php artisan ai:stream-smoke gemini` and `openai` against the unpatched vendor copy. If both pass, delete this patch and the `extra.patches` entry in `composer.json`.
3. If upstream did **not** ship the fix and the patch still applies cleanly, file the PR. Reference the test suite in this repo and the smoke command for verification evidence.

**Where to file it:** https://github.com/laravel/ai
