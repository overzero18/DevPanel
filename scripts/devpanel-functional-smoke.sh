#!/usr/bin/env bash
set -euo pipefail

BASE_URL="${BASE_URL:-http://localhost/devpanel}"
PASSWORD="${DEVPANEL_TEST_PASSWORD:-}"
COOKIE_FILE="$(mktemp)"
PROJECT_DIR="/opt/lampp/htdocs/devpanel/tmp/functional-smoke-project"
TEMPLATE_FILE="/opt/lampp/htdocs/devpanel/logs/project_templates.json"
TEMPLATE_BACKUP=""

cleanup() {
    rm -f "$COOKIE_FILE"
    rm -rf "$PROJECT_DIR"
    if [[ -n "$TEMPLATE_BACKUP" && -f "$TEMPLATE_BACKUP" ]]; then
        cp "$TEMPLATE_BACKUP" "$TEMPLATE_FILE" 2>/dev/null || true
        rm -f "$TEMPLATE_BACKUP"
    fi
}
trap cleanup EXIT

expect_json_success() {
    grep -q '"success":true' <<< "$1"
}

if [[ -z "$PASSWORD" ]]; then
    echo "Define DEVPANEL_TEST_PASSWORD para ejecutar pruebas funcionales." >&2
    exit 1
fi

echo "[1/7] Login"
login_response="$(curl -s -c "$COOKIE_FILE" -d "password=$PASSWORD" "$BASE_URL/api/login.php")"
expect_json_success "$login_response"

dashboard="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/index.php")"
csrf="$(printf '%s' "$dashboard" | sed -n 's/.*csrf-token" content="\([^"]*\)".*/\1/p')"
test -n "$csrf"

echo "[2/7] File Manager write/delete"
mkdir -p "$PROJECT_DIR"
chmod 777 "$PROJECT_DIR"
create_file_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=$PROJECT_DIR" \
    --data-urlencode "name=alpha.txt" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/filemanager/create_file.php")"
expect_json_success "$create_file_response"

save_file_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=$PROJECT_DIR/alpha.txt" \
    --data-urlencode "content=original" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/filemanager/save.php")"
expect_json_success "$save_file_response"
grep -q 'original' "$PROJECT_DIR/alpha.txt"

echo "[3/7] Backup create/preview"
backup_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=$PROJECT_DIR" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/backups/create.php")"
expect_json_success "$backup_response"
backup_file="$(printf '%s' "$backup_response" | /opt/lampp/bin/php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["backup"]["file"] ?? "";')"
test -n "$backup_file"

preview_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/preview.php?file=$backup_file&limit=1000")"
expect_json_success "$preview_response"
grep -q 'alpha.txt' <<< "$preview_response"
versions_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/backups/versions.php?project=functional-smoke-project&file=alpha.txt")"
expect_json_success "$versions_response"
grep -q "$backup_file" <<< "$versions_response"

echo "[4/7] Selective restore"
change_file_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "path=$PROJECT_DIR/alpha.txt" \
    --data-urlencode "content=changed" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/filemanager/save.php")"
expect_json_success "$change_file_response"
restore_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "file=$backup_file" \
    --data-urlencode "files[]=alpha.txt" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/backups/restore.php")"
expect_json_success "$restore_response"
grep -q 'original' "$PROJECT_DIR/alpha.txt"
safety_backup_file="$(printf '%s' "$restore_response" | /opt/lampp/bin/php -r '$data=json_decode(stream_get_contents(STDIN), true); echo $data["restore"]["safety_backup"]["file"] ?? "";')"

echo "[5/7] Backup delete"
delete_backup_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "file=$backup_file" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/backups/delete.php")"
expect_json_success "$delete_backup_response"

if [[ -n "$safety_backup_file" ]]; then
    delete_safety_response="$(curl -s -b "$COOKIE_FILE" \
        --data-urlencode "file=$safety_backup_file" \
        --data-urlencode "csrf_token=$csrf" \
        "$BASE_URL/api/backups/delete.php")"
    expect_json_success "$delete_safety_response"
fi

echo "[6/7] Template marketplace import/list"
if [[ -f "$TEMPLATE_FILE" ]]; then
    TEMPLATE_BACKUP="$(mktemp)"
    cp "$TEMPLATE_FILE" "$TEMPLATE_BACKUP"
fi

template_json='{"key":"functional_smoke","label":"Functional Smoke","description":"Plantilla temporal de smoke test","files":{"index.html":"<h1>Smoke</h1>","README.md":"# Smoke\n"}}'
template_response="$(curl -s -b "$COOKIE_FILE" \
    --data-urlencode "template=$template_json" \
    --data-urlencode "csrf_token=$csrf" \
    "$BASE_URL/api/templates/import.php")"
expect_json_success "$template_response"
templates_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/templates/list.php")"
expect_json_success "$templates_response"
grep -q 'functional_smoke' <<< "$templates_response"

echo "[7/7] Docker assistant and log insights"
docker_setup_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/docker/setup.php")"
expect_json_success "$docker_setup_response"
insights_response="$(curl -s -b "$COOKIE_FILE" "$BASE_URL/api/logs/insights.php")"
expect_json_success "$insights_response"
grep -q '"suggestions"' <<< "$insights_response"

echo "Functional smoke OK"
