#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
WRITE_TESTS="${DEVPANEL_SMOKE_WRITE:-0}"
COOKIE_FILE="$(mktemp)"

cleanup() {
    rm -f "$COOKIE_FILE"
}
trap cleanup EXIT

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para ejecutar pruebas API." >&2
    exit 1
fi

echo "[1/15] Login"
login_response="$(curl -s -c "$COOKIE_FILE" -d "password=$PASSWORD" "$BASE_URL/api/login.php")"
grep -q '"success":true' <<< "$login_response"

echo "[2/15] Dashboard"
dashboard="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/index.php")"
csrf="$(printf '%s' "$dashboard" | sed -n 's/.*csrf-token" content="\([^"]*\)".*/\1/p')"
test -n "$csrf"
grep -q 'Estado global' <<< "$dashboard"
grep -q 'terminalWorkingDirectory' <<< "$dashboard"
grep -q 'DevPanel' <<< "$dashboard"
grep -q 'is-internal' <<< "$dashboard"
curl -s "$BASE_URL/install.php" | grep -q 'Instalación guiada'

echo "[3/15] Assets"
for asset in \
    assets/js/app.js \
    assets/js/modules/system.js \
    assets/js/modules/terminal.js \
    assets/js/filemanager.js \
    assets/css/style.css
do
    asset_code="$(curl -s -o /dev/null -w '%{http_code}' "$BASE_URL/$asset")"
    test "$asset_code" = "200"
done

echo "[4/15] Permisos"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/permissions.php" | grep -q '"success":true'
settings_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/settings.php")"
grep -q 'Ajustes' <<< "$settings_page"
grep -q 'Permisos del sistema' <<< "$settings_page"
grep -q 'Configuración local' <<< "$settings_page"
grep -q 'GitHub' <<< "$settings_page"

echo "[5/15] Logs"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs.php?type=devpanel&lines=25" | grep -q '"success":true'
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/insights.php" | grep -q '"success":true'
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/summary.php" | grep -q '"success":true'

echo "[6/15] Notificaciones"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/notifications/list.php" | grep -q '"success":true'

echo "[7/15] Doctor"
doctor_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/doctor.php")"
grep -q 'Checks del sistema' <<< "$doctor_page"

echo "[8/15] Usuarios"
users_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/users.php")"
grep -q 'Usuarios y roles' <<< "$users_page"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/users/list.php" | grep -q '"success":true'

echo "[9/15] Dominios"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/domains/list.php" | grep -q '"success":true'

echo "[10/15] Backups"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/list.php" | grep -q '"success":true'
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/schedules.php" | grep -q '"success":true'

echo "[11/15] Docker"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/docker/compose.php" | grep -q '"success":true'

echo "[12/15] Stats"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/system_stats.php" | grep -q '"success":true'

echo "[13/15] Terminal"
curl -s -b "$COOKIE_FILE" \
    --data-urlencode "command=pwd" \
    --data-urlencode "cwd=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/terminal.php" | grep -q '"success":true'

curl -s -b "$COOKIE_FILE" \
    --data-urlencode "command=git status" \
    --data-urlencode "cwd=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/terminal.php" | grep -q '"success":true'

echo "[14/15] Git button API"
curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "action=status" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/git/action.php" | grep -q '"success":true'

echo "[15/15] File Manager"
curl -s -b "$COOKIE_FILE" \
    "$BASE_URL/api/filemanager/list.php?path=/opt/lampp/htdocs/devpanel" | grep -q '"success":true'

if [[ "$WRITE_TESTS" == "1" ]]; then
    test_user="devpanel_smoke_$(date +%s)"
    test_password="SmokeTest${RANDOM}Aa!"

    echo "[write] Usuario temporal"
    curl -s -b "$COOKIE_FILE" \
        -d "name=$test_user&role=viewer&password=$test_password&csrf_token=$csrf" \
        "$BASE_URL/api/users/save.php" | grep -q '"success":true'

    curl -s -b "$COOKIE_FILE" \
        -d "name=$test_user&csrf_token=$csrf" \
        "$BASE_URL/api/users/delete.php" | grep -q '"success":true'

    echo "[write] Backup devpanel"
    backup_response="$(curl -s -b "$COOKIE_FILE" -d "path=/opt/lampp/htdocs/devpanel&csrf_token=$csrf" "$BASE_URL/api/backups/create.php")"
    grep -q '"success":true' <<< "$backup_response"
    backup_file="$(printf '%s' "$backup_response" | sed -n 's/.*"file":"\([^"]*\.zip\)".*/\1/p' | head -n 1)"

    if [[ -n "$backup_file" ]]; then
        echo "[write] Backup preview"
        curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/preview.php?file=$backup_file" | grep -q '"success":true'
    fi
fi

echo "Smoke OK"
