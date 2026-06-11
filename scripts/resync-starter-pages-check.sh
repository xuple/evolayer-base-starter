#!/usr/bin/env bash
#
# Verify that starter-owned landing pages were not overwritten by
# composer evolayer:resync. The package publishes its defaults over
# about.tsx and home.tsx; this script exists so the overwrite is
# caught early rather than silently lost.
#
# Usage:
#   bash scripts/resync-starter-pages-check.sh
#
# Exit 0  — both starter-owned pages are present and non-trivial
# Exit 1  — one or both starter-owned pages are missing or appear to
#            be the package default (no starter override marker found)
#
# Add to composer.json scripts as a post-resync step, or run manually
# after every `composer evolayer:resync`.

set -euo pipefail

STARTER_OWNED_PAGES=(
    "resources/js/pages/evolayer/about.tsx"
    "resources/js/pages/evolayer/home.tsx"
)

# The starter overrides include distinctive content that the package
# defaults do not carry. We look for a sentinel string that proves
# the starter override is in place. This is intentionally conservative:
# if the sentinel is missing the script fails loudly, prompting manual
# restoration of the starter override.
SENTINEL="_STARTER_OWNED_PAGE_"

errors=0

for page in "${STARTER_OWNED_PAGES[@]}"; do
    if [ ! -f "$page" ]; then
        echo "MISSING: $page was not found after resync." >&2
        errors=$((errors + 1))
        continue
    fi

    # The package default is always overwritten by the starter
    # override, so we check for content that only the starter version
    # would contain. If the sentinel marker is absent, the file is
    # likely the package default and needs the starter override
    # re-applied.
    if ! grep -q "$SENTINEL" "$page"; then
        echo "OVERWRITTEN: $page does not contain the starter sentinel ($SENTINEL)." >&2
        echo "  The package default overwrote the starter override." >&2
        errors=$((errors + 1))
    fi
done

if [ "$errors" -gt 0 ]; then
    echo "" >&2
    echo "Starter-owned landing page(s) failed the resync safety check." >&2
    echo "Recover with:" >&2
    echo "" >&2
    echo "  git checkout -- resources/js/pages/evolayer/about.tsx resources/js/pages/evolayer/home.tsx" >&2
    echo "  bash scripts/resync-starter-pages-check.sh" >&2
    exit 1
fi

echo "Starter-owned landing pages check passed."
exit 0
