#!/usr/bin/env bash
set -euo pipefail

force=0
version_arg=""

for arg in "$@"; do
    case "$arg" in
        --force|-f)
            force=1
            ;;
        *)
            version_arg="$arg"
            ;;
    esac
done

if [[ -z "$version_arg" ]]; then
    if [[ ! -f VERSION ]]; then
        echo "Uso: $0 1.0.0 [--force]" >&2
        exit 1
    fi

    version_arg="$(tr -d '[:space:]' < VERSION)"
fi

if [[ ! "$version_arg" =~ ^[0-9]+\.[0-9]+\.[0-9]+$ ]]; then
    echo "Uso: $0 1.0.0 [--force]" >&2
    exit 1
fi

./scripts/devpanel-release-check.sh

if [[ -n "$(git status --short)" ]]; then
    echo "El árbol Git no está limpio." >&2
    exit 1
fi

tag="v$version_arg"

if git rev-parse "$tag" >/dev/null 2>&1; then
    if [[ "$(git rev-parse "$tag")" == "$(git rev-parse HEAD)" ]]; then
        echo "Tag ya existe en HEAD: $tag"
        exit 0
    fi

    if [[ "$force" != "1" ]]; then
        echo "El tag $tag ya existe y apunta a otro commit." >&2
        echo "Para moverlo al commit actual ejecuta: $0 $version_arg --force" >&2
        exit 1
    fi

    git tag -f "$tag"
    echo "Tag movido a HEAD: $tag"
    exit 0
fi

git tag "$tag"
echo "Tag creado: $tag"
