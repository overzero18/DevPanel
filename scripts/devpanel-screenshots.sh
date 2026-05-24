#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
CHROMIUM_BIN="${CHROMIUM_BIN:-}"
ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
CAPTURE_FILE="$ROOT_DIR/tmp/screenshot-capture.html"
OUT_DIR="$ROOT_DIR/screenshots/current"
WINDOW_SIZE="${WINDOW_SIZE:-1440,1100}"

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para generar capturas." >&2
    exit 1
fi

if [[ -z "$CHROMIUM_BIN" ]]; then
    CHROMIUM_BIN="$(command -v chromium || command -v chromium-browser || command -v google-chrome || true)"
fi

if [[ -z "$CHROMIUM_BIN" ]]; then
    echo "No se encontró Chromium/Chrome." >&2
    exit 1
fi

mkdir -p "$ROOT_DIR/tmp" "$OUT_DIR"

cat > "$CAPTURE_FILE" <<'HTML'
<!doctype html>
<meta charset="utf-8">
<title>DevPanel screenshot capture</title>
<style>
    html, body { margin: 0; width: 100%; min-height: 100%; background: #0f172a; }
    #status { position: fixed; inset: 0; display: grid; place-items: center; color: #e5e7eb; font: 16px system-ui; }
</style>
<div id="status">Preparando captura...</div>
<script>
(async () => {
    const params = new URLSearchParams(location.search);
    const password = params.get('password') || '';
    const target = params.get('target') || '/devpanel/index.php';

    const login = await fetch('/devpanel/api/login.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: new URLSearchParams({password})
    });
    const data = await login.json();
    if (!data.success) {
        document.getElementById('status').textContent = 'Login fallido';
        return;
    }

    window.location.replace(target);
})();
</script>
HTML

capture() {
    local name="$1"
    local target="$2"
    local temp_file="$HOME/devpanel-$name.png"
    local url="$BASE_URL/tmp/screenshot-capture.html?password=$(printf '%s' "$PASSWORD" | sed 's/%/%25/g; s/&/%26/g; s/+/%2B/g; s/#/%23/g; s/?/%3F/g; s/ /%20/g')&target=$target"

    "$CHROMIUM_BIN" \
        --headless \
        --disable-gpu \
        --no-sandbox \
        --window-size="$WINDOW_SIZE" \
        --virtual-time-budget=5500 \
        --screenshot="$temp_file" \
        "$url"

    cp "$temp_file" "$OUT_DIR/$name.png"
    rm -f "$temp_file"
}

capture dashboard "/devpanel/index.php"
capture settings "/devpanel/settings.php"
capture projects "/devpanel/projects.php"
capture filemanager "/devpanel/filemanager.php"
capture doctor "/devpanel/doctor.php"
capture changelog "/devpanel/changelog.php"
capture about "/devpanel/about.php"

echo "Capturas generadas en $OUT_DIR"
