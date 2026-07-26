#!/usr/bin/env bash

set -euo pipefail

if [[ "$#" -ne 1 ]]; then
    echo "usage: detect-evolayer-base-update.sh <current-version>" >&2
    exit 2
fi

current="$1"

if [[ ! "$current" =~ ^0\.[0-9]+\.[0-9]+(-[0-9A-Za-z.-]+)?$ ]]; then
    echo "current EvoLayer Base version is not an exact 0.x version" >&2
    exit 2
fi

latest=$(
    jq -r '
        (.packages["xuple/evolayer-base"] // [])[]
        | (.version // empty)
        | sub("^v"; "")
        | select(test("^0\\.[0-9]+\\.[0-9]+$"))
    ' |
        sort -V |
        tail -n 1
)

if [[ -z "$latest" ]]; then
    echo "no stable 0.x EvoLayer Base version found" >&2
    exit 1
fi

current_core="${current%%-*}"
newest_core=$(printf '%s\n%s\n' "$current_core" "$latest" | sort -V | tail -n 1)

echo "Packagist latest stable 0.x: $latest" >&2
echo "Current exact pin: $current" >&2

if [[ "$current" == "$latest" || ("$newest_core" == "$current_core" && "$current_core" != "$latest") ]]; then
    printf 'needs_bump=false\n'
    exit 0
fi

printf 'needs_bump=true\n'
printf 'latest=%s\n' "$latest"
printf 'current=%s\n' "$current"
