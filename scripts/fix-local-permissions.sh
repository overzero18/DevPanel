#!/usr/bin/env bash
set -euo pipefail

APACHE_USER="${APACHE_USER:-daemon}"
PROJECT_DIR="${PROJECT_DIR:-/opt/lampp/htdocs/devpanel}"
HTDOCS_DIR="${HTDOCS_DIR:-/opt/lampp/htdocs}"

if [[ ! -d "$PROJECT_DIR" ]]; then
    echo "No existe PROJECT_DIR: $PROJECT_DIR" >&2
    exit 1
fi

echo "Ajustando permisos locales para DevPanel..."
echo "Apache/XAMPP user: $APACHE_USER"
echo "Proyecto: $PROJECT_DIR"
echo "htdocs: $HTDOCS_DIR"

chmod 777 "$PROJECT_DIR" "$PROJECT_DIR/logs" "$PROJECT_DIR/tmp"
chmod 666 "$PROJECT_DIR/config.php"

if command -v setfacl >/dev/null 2>&1; then
    setfacl -m "u:${APACHE_USER}:rwx" "$PROJECT_DIR" "$PROJECT_DIR/logs" "$PROJECT_DIR/tmp" 2>/dev/null || true
    setfacl -m "u:${APACHE_USER}:rw" "$PROJECT_DIR/config.php" 2>/dev/null || true
fi

if [[ "${FIX_HTDOCS:-0}" == "1" ]]; then
    sudo chmod 777 "$HTDOCS_DIR"
fi

echo "Listo. Revisa el panel en: Dashboard > Permisos del sistema"
