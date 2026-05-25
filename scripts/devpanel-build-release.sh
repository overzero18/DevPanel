#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
VERSION="$(tr -d '[:space:]' < "$ROOT_DIR/VERSION")"
RELEASE_DIR="$ROOT_DIR/tmp/releases"
ARCHIVE="$RELEASE_DIR/devpanel-v$VERSION.zip"

mkdir -p "$RELEASE_DIR"
rm -f "$ARCHIVE"

cd "$ROOT_DIR"

zip -qr "$ARCHIVE" . \
    -x ".git/*" \
    -x "config.php" \
    -x "logs/*" \
    -x "tmp/*" \
    -x "node_modules/*" \
    -x "vendor/*" \
    -x "*.zip" \
    -x ".env"

echo "$ARCHIVE"
