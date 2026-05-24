#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
CHROMIUM_BIN="${CHROMIUM_BIN:-}"
TEST_FILE="/opt/lampp/htdocs/devpanel/tmp/visual-smoke.html"
SCREENSHOT_FILE="/opt/lampp/htdocs/devpanel/tmp/visual-smoke-failure.png"

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

        const loadDoc = async (path) => {
            const response = await fetch(path);
            if (!response.ok) throw new Error(`${path} returned ${response.status}`);
            return new DOMParser().parseFromString(await response.text(), 'text/html');
        };

        const assertSelectors = (label, doc, selectors) => {
            return selectors
                .filter(selector => !doc.querySelector(selector))
                .map(selector => `${label} ${selector}`);
        };

        const dashboardDoc = await loadDoc('/devpanel/index.php');
        const installDoc = await loadDoc('/devpanel/install.php');
        const doctorDoc = await loadDoc('/devpanel/doctor.php');
        const settingsDoc = await loadDoc('/devpanel/settings.php');
        const usersDoc = await loadDoc('/devpanel/users.php');
        const projectsDoc = await loadDoc('/devpanel/projects.php');
        const fileManagerDoc = await loadDoc('/devpanel/filemanager.php');
        const auditDoc = await loadDoc('/devpanel/audit.php');

        const missing = [
            ...assertSelectors('dashboard', dashboardDoc, [
                '#systemHealthGrid',
                '#terminalWorkingDirectory',
                '#terminal',
                '#fileManagerContent',
                '#logSummaryGrid',
                '#backupScheduleList',
                '#onboardingChecklist',
                '[onclick*="openProjectTerminal"]',
                '[onclick*="copyTerminalOutput"]',
                '[onclick*="saveBackupSchedule"]',
                'script[src*="modules/terminal.js?v="]',
                'script[src*="modules/logs.js?v="]',
                'link[href*="style.css?v="]',
                'script[src*="codemirror"]'
            ]),
            ...assertSelectors('install', installDoc, [
                '.install-page',
                '.install-summary-grid',
                '.install-step-list',
                '.doctor-check-list',
                '.doctor-command-list',
                'a[href="/devpanel/doctor.php"]'
            ]),
            ...assertSelectors('doctor', doctorDoc, [
                '.doctor-summary-grid',
                '.doctor-check-list',
                '.doctor-command-list',
                '.doctor-summary-card.is-info'
            ]),
            ...assertSelectors('settings', settingsDoc, [
                '#permissionsList',
                '#runtime-settings',
                '#security-settings',
                '#githubRemoteUrl',
                '#apiTokenList',
                '#apiTokenExpiry',
                '#twoFactorToggle',
                '#twoFactorQr',
                '#configImportFile',
                '#dashboardWidgetSettings',
                '#template-marketplace',
                '#theme-customizer',
                '[onclick*="saveRuntimeSettings"]',
                '[onclick*="saveGithubSettings"]'
            ]),
            ...assertSelectors('users', usersDoc, [
                '#adminUserName',
                '#adminUserRole',
                '#adminUserProjects',
                '#adminUsersList',
                '#adminPermissionsList',
                '#adminRolesList',
                '[onclick*="saveAdminUser"]'
            ]),
            ...assertSelectors('projects', projectsDoc, [
                '.content',
                'a[href="/devpanel/index.php#projects"]',
                '.project-detail-card, .file-manager-empty'
            ]),
            ...assertSelectors('filemanager', fileManagerDoc, [
                '#fileManagerContent',
                '#fileManagerTree',
                '#fileManagerPath',
                '#fileEditorStatus',
                '[onclick*="createFileManagerFile"]',
                '#fileManagerUpload'
            ]),
            ...assertSelectors('docker', dashboardDoc, [
                '#dockerSetupAssistant'
            ]),
            ...assertSelectors('audit', auditDoc, [
                '#auditList',
                '#auditSearch',
                '#auditAction',
                '#auditUser',
                '[onclick*="loadAuditLog"]'
            ])
        ];

        if (missing.length) {
            throw new Error(`missing ${missing.join(', ')}`);
        }

        const jsonChecks = [
            ['/devpanel/api/logs/summary.php', 'logs summary'],
            ['/devpanel/api/permissions.php', 'permissions'],
            ['/devpanel/api/users/list.php', 'users list'],
            ['/devpanel/api/backups/schedules.php', 'backup schedules']
        ];

        for (const [path, label] of jsonChecks) {
            const response = await fetch(path);
            if (!response.ok) throw new Error(`${label} returned ${response.status}`);
            await response.json();
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
test_url="$BASE_URL/tmp/visual-smoke.html?password=$encoded_password"
output="$("$CHROMIUM_BIN" --headless --disable-gpu --no-sandbox --dump-dom --virtual-time-budget=3500 "$test_url")"

if ! grep -q 'VISUAL_SMOKE_OK' <<< "$output"; then
    "$CHROMIUM_BIN" --headless --disable-gpu --no-sandbox --screenshot="$SCREENSHOT_FILE" --window-size=1440,1100 --virtual-time-budget=3500 "$test_url" >/dev/null 2>&1 || true
    echo "$output" | sed -n '1,120p'
    echo "Screenshot de fallo: $SCREENSHOT_FILE"
    exit 1
fi

echo "Visual smoke OK"
