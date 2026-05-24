#!/usr/bin/env bash
set -euo pipefail

APACHE_USER="${APACHE_USER:-daemon}"
PROJECT_DIR="${PROJECT_DIR:-/opt/lampp/htdocs/devpanel}"
HTDOCS_DIR="${HTDOCS_DIR:-/opt/lampp/htdocs}"
LOCAL_USER="${LOCAL_USER:-${SUDO_USER:-$USER}}"

if [[ ! -d "$PROJECT_DIR" ]]; then
    echo "No existe PROJECT_DIR: $PROJECT_DIR" >&2
    exit 1
fi

echo "Ajustando permisos locales para DevPanel..."
echo "Apache/XAMPP user: $APACHE_USER"
echo "Local user: $LOCAL_USER"
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

if [[ "${FIX_DOCKER:-0}" == "1" ]]; then
    if [[ ! -S /var/run/docker.sock ]]; then
        echo "No existe /var/run/docker.sock. Arranca Docker primero." >&2
        exit 1
    fi

    sudo usermod -aG docker "$LOCAL_USER" 2>/dev/null || true

    if command -v setfacl >/dev/null 2>&1; then
        sudo setfacl -m "u:${LOCAL_USER}:rw" /var/run/docker.sock
        sudo setfacl -m "u:${APACHE_USER}:rw" /var/run/docker.sock 2>/dev/null || true
    else
        echo "setfacl no está instalado; instala acl o vuelve a iniciar sesión tras añadir el grupo docker." >&2
    fi
fi

echo "Listo. Revisa el panel en: Dashboard > Permisos del sistema"
