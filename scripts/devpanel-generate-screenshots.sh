#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEVPANEL_TEST_PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
PORT="${DEVPANEL_PORT:-80}"
SCREENSHOT_DIR="$ROOT_DIR/screenshots/current"

if [[ -z "$DEVPANEL_TEST_PASSWORD" ]]; then
    echo "❌ DEVPANEL_TEST_PASSWORD not set"
    exit 1
fi

mkdir -p "$SCREENSHOT_DIR"

BROWSER_BIN=""
for cmd in chromium chromium-browser google-chrome google-chrome-stable; do
    if command -v "$cmd" &>/dev/null; then
        BROWSER_BIN="$cmd"
        break
    fi
done

if [[ -z "$BROWSER_BIN" ]]; then
    echo "❌ Chromium/Chrome not found"
    exit 1
fi

echo "📸 Generating screenshots for README..."

take_screenshot() {
    local name="$1"
    local url="$2"
    local file="$SCREENSHOT_DIR/${name}.png"

    echo -n "  $name ... "

    timeout 10 "$BROWSER_BIN" \
        --headless \
        --disable-gpu \
        --no-sandbox \
        --screenshot="$file" \
        --window-size=1440,1080 \
        "http://localhost:${PORT}/devpanel/$url" \
        >/dev/null 2>&1 && echo "✓" || echo "⚠"
}

# Get session cookies
COOKIE_JAR="/tmp/devpanel_cookies.txt"
curl -s -c "$COOKIE_JAR" -X POST \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    -d "password=$DEVPANEL_TEST_PASSWORD" \
    "http://localhost:${PORT}/devpanel/api/auth/login.php" >/dev/null 2>&1 || true

# Take screenshots of all main pages
take_screenshot "dashboard" "index.php"
take_screenshot "projects" "projects.php"
take_screenshot "filemanager" "filemanager.php"
take_screenshot "settings" "settings.php"
take_screenshot "releases" "releases.php"
take_screenshot "audit" "audit.php"
take_screenshot "about" "about.php"
take_screenshot "doctor" "doctor.php"

rm -f "$COOKIE_JAR"

echo ""
echo "✅ Screenshots generated in $SCREENSHOT_DIR"
