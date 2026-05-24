#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PHP_BIN="${PHP_BIN:-/opt/lampp/bin/php}"

if [[ ! -x "$PHP_BIN" ]]; then
    PHP_BIN="$(command -v php || true)"
fi

if [[ -z "$PHP_BIN" ]]; then
    echo "No se encontró PHP. Define PHP_BIN=/ruta/php." >&2
    exit 1
fi

cd "$ROOT_DIR"

echo "[1/6] PHP lint"
while IFS= read -r -d '' file; do
    "$PHP_BIN" -l "$file" >/dev/null
done < <(find . -path './vendor' -prune -o -path './tmp' -prune -o -path './logs' -prune -o -name '*.php' -print0)

echo "[2/6] Bash syntax"
while IFS= read -r -d '' file; do
    bash -n "$file"
done < <(find scripts -name '*.sh' -print0)

echo "[3/6] Git whitespace"
git diff --check

echo "[4/6] Private config guard"
if git ls-files --error-unmatch config.php >/dev/null 2>&1; then
    echo "config.php está trackeado. No lo subas a un repositorio público." >&2
    exit 1
fi

echo "[5/6] Secret pattern guard"
if git ls-files -z | xargs -0 grep -InE '(dp_[A-Za-z0-9]{32,}|ghp_[A-Za-z0-9_]{20,}|github_pat_[A-Za-z0-9_]+)' -- 2>/dev/null; then
    echo "Se detectó un posible secreto en archivos trackeados." >&2
    exit 1
fi

echo "[6/6] Public docs"
test -f README.md
test -f INSTALL.md
test -f config.example.php

echo "Release check OK"
