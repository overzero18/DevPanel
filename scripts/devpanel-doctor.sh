#!/usr/bin/env bash
set -euo pipefail

PROJECT_DIR="${PROJECT_DIR:-$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)}"
HTDOCS_DIR="${HTDOCS_DIR:-/opt/lampp/htdocs}"
LAMPP_DIR="${LAMPP_DIR:-/opt/lampp}"
APACHE_USER="${APACHE_USER:-daemon}"

ok=0
warn=0
fail=0

print_check() {
    local status="$1"
    local label="$2"
    local detail="${3:-}"

    case "$status" in
        ok) printf "[OK]   %s%s\n" "$label" "${detail:+ - $detail}"; ok=$((ok + 1)) ;;
        warn) printf "[WARN] %s%s\n" "$label" "${detail:+ - $detail}"; warn=$((warn + 1)) ;;
        fail) printf "[FAIL] %s%s\n" "$label" "${detail:+ - $detail}"; fail=$((fail + 1)) ;;
    esac
}

check_file() {
    local path="$1"
    local label="$2"
    [[ -f "$path" ]] && print_check ok "$label" "$path" || print_check fail "$label" "$path no existe"
}

check_dir() {
    local path="$1"
    local label="$2"
    [[ -d "$path" ]] && print_check ok "$label" "$path" || print_check fail "$label" "$path no existe"
}

check_writable() {
    local path="$1"
    local label="$2"
    [[ -w "$path" ]] && print_check ok "$label" "escribible" || print_check warn "$label" "sin escritura"
}

echo "DevPanel Doctor"
echo "Proyecto: $PROJECT_DIR"
echo

check_dir "$PROJECT_DIR" "Proyecto"
check_dir "$HTDOCS_DIR" "htdocs"
check_dir "$PROJECT_DIR/logs" "logs"
check_dir "$PROJECT_DIR/tmp" "tmp"
check_file "$PROJECT_DIR/config.example.php" "config.example.php"

if [[ -f "$PROJECT_DIR/config.php" ]]; then
    print_check ok "config.php" "config local presente"
else
    print_check warn "config.php" "crealo desde setup.php o copia config.example.php"
fi

check_file "$LAMPP_DIR/lampp" "LAMPP"
check_file "$LAMPP_DIR/bin/php" "PHP XAMPP"

check_writable "$PROJECT_DIR/config.php" "config.php escritura"
check_writable "$PROJECT_DIR/logs" "logs escritura"
check_writable "$PROJECT_DIR/tmp" "tmp escritura"
check_writable "$HTDOCS_DIR" "htdocs escritura"

if command -v docker >/dev/null 2>&1; then
    print_check ok "Docker" "$(docker --version 2>/dev/null || true)"
    if docker ps >/dev/null 2>&1; then
        print_check ok "Docker daemon" "Docker responde correctamente"
    else
        print_check warn "Docker daemon" "sin acceso al socket; usa FIX_DOCKER=1"
    fi
else
    print_check warn "Docker" "no instalado o fuera de PATH"
fi

if command -v qrencode >/dev/null 2>&1; then
    print_check ok "qrencode" "$(qrencode --version 2>&1 | head -n 1)"
else
    print_check warn "qrencode" "opcional para QR 2FA real"
fi

if command -v git >/dev/null 2>&1; then
    print_check ok "Git" "$(git --version)"
else
    print_check fail "Git" "no instalado"
fi

if "$LAMPP_DIR/bin/php" -v >/dev/null 2>&1; then
    print_check ok "PHP ejecutable" "$("$LAMPP_DIR/bin/php" -r 'echo PHP_VERSION;')"
else
    print_check fail "PHP ejecutable" "no responde"
fi

echo
echo "Resumen: OK=$ok WARN=$warn FAIL=$fail"

if (( warn > 0 )); then
    echo
    echo "Para permisos locales puedes ejecutar:"
    echo "  APACHE_USER=$APACHE_USER ./scripts/fix-local-permissions.sh"
    echo "  FIX_HTDOCS=1 APACHE_USER=$APACHE_USER ./scripts/fix-local-permissions.sh"
    echo "  FIX_DOCKER=1 APACHE_USER=$APACHE_USER ./scripts/fix-local-permissions.sh"
fi

exit "$fail"
