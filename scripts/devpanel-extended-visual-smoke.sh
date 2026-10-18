#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEVPANEL_TEST_PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
PORT="${DEVPANEL_PORT:-80}"
CHROMIUM_BIN="${CHROMIUM_BIN:-chromium}"

if [[ -z "$DEVPANEL_TEST_PASSWORD" ]]; then
    echo "❌ DEVPANEL_TEST_PASSWORD not set"
    exit 1
fi

if ! command -v "$CHROMIUM_BIN" &>/dev/null; then
    echo "❌ Chromium not found: $CHROMIUM_BIN"
    exit 1
fi

BASE_URL="http://localhost:${PORT}/devpanel"
SCREENSHOT_DIR="$ROOT_DIR/tmp"
mkdir -p "$SCREENSHOT_DIR"

test_page() {
    local name="$1"
    local url="$2"
    local selectors="$3"
    local cookie_file
    local html

    echo -n "Testing: $name ... "

    cookie_file="$(mktemp)"
    trap 'rm -f "$cookie_file"' RETURN

    curl -fsS -c "$cookie_file" \
        -d "password=$DEVPANEL_TEST_PASSWORD" \
        "$BASE_URL/api/login.php" | grep -q '"success":true'

    html="$(curl -fsS -b "$cookie_file" "$BASE_URL/$url")"

    IFS=',' read -ra selector_list <<< "$selectors"
    for selector in "${selector_list[@]}"; do
        selector="$(echo "$selector" | xargs)"
        fragment="$(printf '%s' "$selector" | sed -E 's/^[.#]//; s/^\[([^]=]+).*/\1/')"

        if ! grep -Fq "$fragment" <<< "$html"; then
            echo "❌ Missing selector: $selector"
            return 1
        fi
    done

    echo "✓"
}

echo "🧪 Running expanded visual smoke tests..."
echo ""

# Core pages
test_page "Dashboard" "index.php" ".dashboard-card, #systemHealthGrid, #terminal"
test_page "Projects" "projects.php" ".projects-list, [data-project-name]"
test_page "File Manager" "filemanager.php" "#fileManagerContent, .file-tree"
test_page "Settings" "settings.php" "#settingsForm, [data-setting]"
test_page "Users" "users.php" ".users-list, [data-user-id]"
test_page "Audit" "audit.php" ".audit-list, [data-audit-entry]"
test_page "Changelog" "changelog.php" ".changelog-list, [data-version]"
test_page "About" "about.php" ".system-info, [data-system-stat]"
test_page "Doctor" "doctor.php" ".doctor-check-list, [data-check-id]"
test_page "CI Health" "ci.php" ".ci-status, [data-check-name]"

# New pages
test_page "Releases" "releases.php" ".release-notes, [data-release-tag]"
test_page "Installer" "install.php" ".install-summary-grid, .install-step-list"

echo ""
echo "✅ All visual smoke tests passed"
