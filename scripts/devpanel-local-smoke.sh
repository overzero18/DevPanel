#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"

export DEVPANEL_REQUIRE_TOKEN_AUTH="${DEVPANEL_REQUIRE_TOKEN_AUTH:-1}"

bash "$ROOT_DIR/scripts/devpanel-api-smoke.sh"
bash "$ROOT_DIR/scripts/devpanel-functional-smoke.sh"
bash "$ROOT_DIR/scripts/devpanel-extended-functional-smoke.sh"
bash "$ROOT_DIR/scripts/devpanel-visual-smoke.sh"
bash "$ROOT_DIR/scripts/devpanel-extended-visual-smoke.sh"

