#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
CHROMIUM_BIN="${CHROMIUM_BIN:-}"
TEST_FILE="/opt/lampp/htdocs/devpanel/tmp/visual-smoke.html"

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para ejecutar el test visual." >&2
    exit 1
fi

if [[ -z "$CHROMIUM_BIN" ]]; then
    CHROMIUM_BIN="$(command -v chromium || command -v chromium-browser || command -v google-chrome || true)"
fi

if [[ -z "$CHROMIUM_BIN" ]]; then
    echo "No se encontró Chromium/Chrome." >&2
    exit 1
fi

mkdir -p "$(dirname "$TEST_FILE")"

cat > "$TEST_FILE" <<'HTML'
<!doctype html>
<meta charset="utf-8">
<title>DevPanel visual smoke</title>
<pre id="result">RUNNING</pre>
<script>
(async () => {
    const result = document.getElementById('result');
    const params = new URLSearchParams(location.search);
    const password = params.get('password') || '';

    try {
        const login = await fetch('/devpanel/api/login.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({password})
        });
        const loginData = await login.json();

        if (!loginData.success) throw new Error('login failed');

        const dashboardResponse = await fetch('/devpanel/index.php');
        const dashboard = await dashboardResponse.text();
        const doc = new DOMParser().parseFromString(dashboard, 'text/html');
        const settingsResponse = await fetch('/devpanel/settings.php');
        const settings = await settingsResponse.text();
        const settingsDoc = new DOMParser().parseFromString(settings, 'text/html');
        const required = [
            '#systemHealthGrid',
            '#terminalWorkingDirectory',
            '#terminal',
            '#fileManagerContent',
            '#logSummaryGrid',
            '#backupScheduleList',
            '[onclick*="openProjectTerminal"]',
            '[onclick*="copyTerminalOutput"]',
            '[onclick*="saveBackupSchedule"]',
            'script[src*="modules/terminal.js?v="]',
            'script[src*="modules/logs.js?v="]',
            'link[href*="style.css?v="]',
            'script[src*="codemirror"]'
        ];
        const settingsRequired = [
            '#permissionsList',
            '#runtime-settings',
            '#githubRemoteUrl',
            '[onclick*="saveRuntimeSettings"]',
            '[onclick*="saveGithubSettings"]'
        ];
        const missing = [
            ...required.filter(selector => !doc.querySelector(selector)),
            ...settingsRequired
                .filter(selector => !settingsDoc.querySelector(selector))
                .map(selector => `settings ${selector}`)
        ];

        if (missing.length) {
            throw new Error(`missing ${missing.join(', ')}`);
        }

        result.textContent = 'VISUAL_SMOKE_OK';
    }
    catch(error) {
        result.textContent = `VISUAL_SMOKE_FAIL ${error.message}`;
    }
})();
</script>
HTML

encoded_password="$(printf '%s' "$PASSWORD" | sed 's/%/%25/g; s/&/%26/g; s/+/%2B/g; s/#/%23/g; s/?/%3F/g; s/ /%20/g')"
output="$("$CHROMIUM_BIN" --headless --disable-gpu --no-sandbox --dump-dom --virtual-time-budget=3500 "$BASE_URL/tmp/visual-smoke.html?password=$encoded_password")"

if ! grep -q 'VISUAL_SMOKE_OK' <<< "$output"; then
    echo "$output" | sed -n '1,120p'
    exit 1
fi

echo "Visual smoke OK"
