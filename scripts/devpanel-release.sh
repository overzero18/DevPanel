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

tag="v$VERSION"

if git rev-parse "$tag" >/dev/null 2>&1; then
    if [[ "$(git rev-parse "$tag")" == "$(git rev-parse HEAD)" ]]; then
        echo "Tag ya existe en HEAD: $tag"
        exit 0
    fi

    echo "El tag $tag ya existe y apunta a otro commit." >&2
    echo "Si quieres moverlo manualmente: git tag -f $tag" >&2
    exit 1
fi

git tag "$tag"
echo "Tag creado: $tag"
