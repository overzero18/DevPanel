#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DEVPANEL_TEST_PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
PORT="${DEVPANEL_PORT:-80}"
GIF_DIR="$ROOT_DIR/screenshots/current"
FRAMES_DIR="/tmp/devpanel_gif_frames"

if [[ -z "$DEVPANEL_TEST_PASSWORD" ]]; then
    echo "❌ DEVPANEL_TEST_PASSWORD not set"
    exit 1
fi

mkdir -p "$FRAMES_DIR"

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

if ! command -v convert &>/dev/null; then
    echo "❌ ImageMagick not found (convert command)"
    exit 1
fi

echo "🎬 Generating demo GIF..."

take_frame() {
    local frame_num="$1"
    local url="$2"
    local file="$FRAMES_DIR/frame_$(printf "%02d" $frame_num).png"

    timeout 8 "$BROWSER_BIN" \
        --headless \
        --disable-gpu \
        --no-sandbox \
        --screenshot="$file" \
        --window-size=1440,900 \
        "http://localhost:${PORT}/devpanel/$url" \
        >/dev/null 2>&1 || true

    echo "$file"
}

# Get session first
COOKIE_JAR="/tmp/devpanel_cookies.txt"
curl -s -c "$COOKIE_JAR" -X POST \
    -H 'Content-Type: application/x-www-form-urlencoded' \
    -d "password=$DEVPANEL_TEST_PASSWORD" \
    "http://localhost:${PORT}/devpanel/api/auth/login.php" >/dev/null 2>&1 || true

echo "Capturing frames..."
take_frame 1 "index.php"
take_frame 2 "projects.php"
take_frame 3 "filemanager.php"
take_frame 4 "settings.php"
take_frame 5 "releases.php"

# Create GIF with ImageMagick
echo "Creating GIF animation..."
convert -delay 150 -loop 0 -density 96 \
    "$FRAMES_DIR/frame_*.png" \
    "$GIF_DIR/devpanel-demo.gif"

# Cleanup
rm -rf "$FRAMES_DIR" "$COOKIE_JAR"

echo "✅ Demo GIF created: $GIF_DIR/devpanel-demo.gif"
