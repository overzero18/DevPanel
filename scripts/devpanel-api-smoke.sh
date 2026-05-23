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

echo "[1/10] Login"
login_response="$(curl -s -c "$COOKIE_FILE" -d "password=$PASSWORD" "$BASE_URL/api/login.php")"
echo "$login_response" | grep -q '"success":true'

echo "[2/10] Dashboard"
dashboard="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/index.php")"
csrf="$(printf '%s' "$dashboard" | sed -n 's/.*csrf-token" content="\([^"]*\)".*/\1/p')"
test -n "$csrf"

echo "[3/10] Permisos"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/permissions.php" | grep -q '"success":true'

echo "[4/10] Logs"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs.php?type=devpanel&lines=25" | grep -q '"success":true'
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/insights.php" | grep -q '"success":true'

echo "[5/10] Notificaciones"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/notifications/list.php" | grep -q '"success":true'

echo "[6/10] Doctor"
curl -s -b "$COOKIE_FILE" "$BASE_URL/doctor.php" | grep -q 'Checks del sistema'

echo "[7/10] Dominios"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/domains/list.php" | grep -q '"success":true'

echo "[8/10] Backups"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/list.php" | grep -q '"success":true'

echo "[9/10] Docker"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/docker/compose.php" | grep -q '"success":true'

echo "[10/10] Stats"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/system_stats.php" | grep -q '"success":true'

if [[ "$WRITE_TESTS" == "1" ]]; then
    echo "[write] Backup devpanel"
    curl -s -b "$COOKIE_FILE" -d "path=/opt/lampp/htdocs/devpanel&csrf_token=$csrf" "$BASE_URL/api/backups/create.php" | grep -q '"success":true'
fi

echo "Smoke OK"
