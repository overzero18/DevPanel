#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
COOKIE_FILE="$(mktemp)"

cleanup() {
    rm -f "$COOKIE_FILE"
}
trap cleanup EXIT

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para ejecutar pruebas API." >&2
    exit 1
fi

echo "[1/6] Login"
login_response="$(curl -s -c "$COOKIE_FILE" -d "password=$PASSWORD" "$BASE_URL/api/login.php")"
echo "$login_response" | grep -q '"success":true'

echo "[2/6] Dashboard"
dashboard="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/index.php")"
csrf="$(printf '%s' "$dashboard" | sed -n 's/.*csrf-token" content="\([^"]*\)".*/\1/p')"
test -n "$csrf"

echo "[3/6] Permisos"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/permissions.php" | grep -q '"success":true'

echo "[4/6] Logs"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs.php?type=devpanel&lines=25" | grep -q '"success":true'

echo "[5/6] Notificaciones"
curl -s -b "$COOKIE_FILE" "$BASE_URL/api/notifications/list.php" | grep -q '"success":true'

echo "[6/6] Doctor"
curl -s -b "$COOKIE_FILE" "$BASE_URL/doctor.php" | grep -q 'Checks del sistema'

echo "Smoke OK"
