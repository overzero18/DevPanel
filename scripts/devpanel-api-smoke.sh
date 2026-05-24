#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
WRITE_TESTS="${DEVPANEL_SMOKE_WRITE:-0}"
COOKIE_FILE="$(mktemp)"
CONFIG_BACKUP=""

cleanup() {
    rm -f "$COOKIE_FILE"
    if [[ -n "$CONFIG_BACKUP" && -f "$CONFIG_BACKUP" ]]; then
        cp "$CONFIG_BACKUP" /opt/lampp/htdocs/devpanel/config.php
        rm -f "$CONFIG_BACKUP"
    fi
}
trap cleanup EXIT

expect_json_success() {
    grep -q '"success":true' <<< "$1"
}

expect_contains() {
    grep -q "$2" <<< "$1"
}

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para ejecutar pruebas API." >&2
    exit 1
fi

echo "[1/15] Login"
login_response="$(curl -s -c "$COOKIE_FILE" -d "password=$PASSWORD" "$BASE_URL/api/login.php")"
expect_json_success "$login_response"

echo "[2/15] Dashboard"
dashboard="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/index.php")"
csrf="$(printf '%s' "$dashboard" | sed -n 's/.*csrf-token" content="\([^"]*\)".*/\1/p')"
test -n "$csrf"
grep -q 'Estado global' <<< "$dashboard"
grep -q 'terminalWorkingDirectory' <<< "$dashboard"
grep -q 'DevPanel' <<< "$dashboard"
grep -q 'is-internal' <<< "$dashboard"
install_page="$(curl -s "$BASE_URL/install.php")"
expect_contains "$install_page" 'Instalación guiada'

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
permissions_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/permissions.php")"
security_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/security/settings.php")"
expect_json_success "$permissions_response"
expect_json_success "$security_response"
settings_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/settings.php")"
grep -q 'Ajustes' <<< "$settings_page"
grep -q 'Permisos del sistema' <<< "$settings_page"
grep -q 'Configuración local' <<< "$settings_page"
grep -q 'GitHub' <<< "$settings_page"
grep -q 'Seguridad avanzada' <<< "$settings_page"

echo "[4b/15] API token"
token_response="$(curl -s -b "$COOKIE_FILE" \
    -d "name=smoke-token&role=viewer&expires_days=7&csrf_token=$csrf" \
    "$BASE_URL/api/tokens/create.php")"
expect_json_success "$token_response"
api_token="$(printf '%s' "$token_response" | /opt/lampp/bin/php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["token"] ?? "";')"
api_token_id="$(printf '%s' "$token_response" | /opt/lampp/bin/php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["item"]["id"] ?? "";')"
test -n "$api_token"
token_summary="$(curl -s -H "X-DevPanel-Token: $api_token" "$BASE_URL/api/logs/summary.php")"
expect_json_success "$token_summary"
token_settings="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/security/settings.php")"
printf '%s' "$token_settings" | grep -q '"last_used_at":"'
rotate_token_response="$(curl -s -b "$COOKIE_FILE" \
    -d "id=$api_token_id&csrf_token=$csrf" \
    "$BASE_URL/api/tokens/rotate.php")"
expect_json_success "$rotate_token_response"
api_token_id="$(printf '%s' "$rotate_token_response" | /opt/lampp/bin/php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["item"]["id"] ?? "";')"
delete_token_response="$(curl -s -b "$COOKIE_FILE" \
    -d "id=$api_token_id&csrf_token=$csrf" \
    "$BASE_URL/api/tokens/delete.php")"
expect_json_success "$delete_token_response"

CONFIG_BACKUP="$(mktemp)"
cp /opt/lampp/htdocs/devpanel/config.php "$CONFIG_BACKUP"
expired_token="$(/opt/lampp/bin/php -r 'echo "dp_" . bin2hex(random_bytes(24));')"
expired_token_hash="$(/opt/lampp/bin/php -r 'echo password_hash($argv[1], PASSWORD_BCRYPT, ["cost" => 10]);' "$expired_token")"
/opt/lampp/bin/php -r '
$file = "/opt/lampp/htdocs/devpanel/config.php";
$config = require $file;
$config["DEVPANEL_API_TOKENS"][] = [
    "id" => hash("sha256", $argv[1]),
    "name" => "expired-smoke-token",
    "prefix" => substr($argv[1], 0, 10),
    "hash" => $argv[2],
    "role" => "viewer",
    "created_at" => date("Y-m-d H:i:s", time() - 172800),
    "last_used_at" => null,
    "expires_at" => date("Y-m-d H:i:s", time() - 86400),
];
$content = "<?php\n\nreturn " . var_export($config, true) . ";\n";
file_put_contents($file, $content, LOCK_EX);
' "$expired_token" "$expired_token_hash"
expired_code="$(curl -s -o /dev/null -w '%{http_code}' -H "X-DevPanel-Token: $expired_token" "$BASE_URL/api/logs/summary.php")"
test "$expired_code" = "401"
cp "$CONFIG_BACKUP" /opt/lampp/htdocs/devpanel/config.php
rm -f "$CONFIG_BACKUP"
CONFIG_BACKUP=""

echo "[5/15] Logs"
logs_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs.php?type=devpanel&lines=25")"
insights_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/insights.php")"
summary_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/summary.php")"
audit_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/audit/list.php?limit=25")"
demo_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/demo/status.php")"
theme_marketplace_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/themes/marketplace.php")"
updater_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/updater/status.php")"
expect_json_success "$logs_response"
expect_json_success "$insights_response"
expect_json_success "$summary_response"
expect_json_success "$audit_response"
expect_json_success "$demo_response"
expect_json_success "$theme_marketplace_response"
expect_json_success "$updater_response"

echo "[6/15] Notificaciones"
notifications_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/notifications/list.php")"
expect_json_success "$notifications_response"

echo "[7/15] Doctor"
doctor_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/doctor.php")"
grep -q 'Checks del sistema' <<< "$doctor_page"

echo "[8/15] Usuarios"
users_page="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/users.php")"
grep -q 'Usuarios y roles' <<< "$users_page"
users_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/users/list.php")"
expect_json_success "$users_response"

echo "[9/15] Dominios"
domains_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/domains/list.php")"
expect_json_success "$domains_response"

echo "[10/15] Backups"
backups_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/list.php")"
schedules_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/schedules.php")"
expect_json_success "$backups_response"
expect_json_success "$schedules_response"

echo "[11/15] Docker"
docker_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/docker/compose.php")"
expect_json_success "$docker_response"

echo "[12/15] Stats"
stats_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/system_stats.php")"
expect_json_success "$stats_response"

echo "[13/15] Terminal"
terminal_pwd_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "command=pwd" \
    --data-urlencode "cwd=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/terminal.php")"
expect_json_success "$terminal_pwd_response"

terminal_git_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "command=git status" \
    --data-urlencode "cwd=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/terminal.php")"
expect_json_success "$terminal_git_response"

echo "[14/15] Git button API"
git_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=/opt/lampp/htdocs/devpanel" \
    --data-urlencode "action=status" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/git/action.php")"
expect_json_success "$git_response"

echo "[15/15] File Manager"
filemanager_response="$(curl -s -b "$COOKIE_FILE" \
    "$BASE_URL/api/filemanager/list.php?path=/opt/lampp/htdocs/devpanel")"
expect_json_success "$filemanager_response"

if [[ "$WRITE_TESTS" == "1" ]]; then
    test_user="devpanel_smoke_$(date +%s)"
    test_password="SmokeTest${RANDOM}Aa!"

    echo "[write] Usuario temporal"
    save_user_response="$(curl -s -b "$COOKIE_FILE" \
        -d "name=$test_user&role=viewer&password=$test_password&csrf_token=$csrf" \
        "$BASE_URL/api/users/save.php")"
    expect_json_success "$save_user_response"

    delete_user_response="$(curl -s -b "$COOKIE_FILE" \
        -d "name=$test_user&csrf_token=$csrf" \
        "$BASE_URL/api/users/delete.php")"
    expect_json_success "$delete_user_response"

    echo "[write] Backup devpanel"
    backup_response="$(curl -s -b "$COOKIE_FILE" -d "path=/opt/lampp/htdocs/devpanel&csrf_token=$csrf" "$BASE_URL/api/backups/create.php")"
    grep -q '"success":true' <<< "$backup_response"
    backup_file="$(printf '%s' "$backup_response" | sed -n 's/.*"file":"\([^"]*\.zip\)".*/\1/p' | head -n 1)"

    if [[ -n "$backup_file" ]]; then
        echo "[write] Backup preview"
        preview_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/preview.php?file=$backup_file")"
        expect_json_success "$preview_response"
    fi
fi

echo "Smoke OK"
