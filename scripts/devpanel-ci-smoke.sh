#!/usr/bin/env bash
set -euo pipefail

export DEVPANEL_REQUIRE_TOKEN_AUTH="${DEVPANEL_REQUIRE_TOKEN_AUTH:-0}"
export DEVPANEL_SMOKE_WRITE="${DEVPANEL_SMOKE_WRITE:-0}"

bash "$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)/devpanel-api-smoke.sh"
