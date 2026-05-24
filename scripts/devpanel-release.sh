#!/usr/bin/env bash
set -euo pipefail

VERSION="${1:-$(tr -d '[:space:]' < VERSION)}"

if [[ ! "$VERSION" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Uso: $0 1.0.0" >&2
    exit 1
fi

./scripts/devpanel-release-check.sh

if [[ -n "$(git status --short)" ]]; then
    echo "El árbol Git no está limpio." >&2
    exit 1
fi

git tag "v$VERSION"
echo "Tag creado: v$VERSION"
