#!/usr/bin/env bash
set -euo pipefail

TARGET_DIR="${TARGET_DIR:-/opt/lampp/htdocs/devpanel}"
APACHE_USER="${APACHE_USER:-daemon}"

echo "DevPanel installer"
echo "Destino: $TARGET_DIR"

if [[ ! -d /opt/lampp ]]; then
    echo "No se encontró /opt/lampp. Instala XAMPP primero." >&2
    exit 1
fi

sudo mkdir -p "$TARGET_DIR"
sudo rsync -a --delete \
    --exclude .git \
    --exclude config.php \
    --exclude logs \
    --exclude tmp \
    --exclude node_modules \
    ./ "$TARGET_DIR"/

sudo mkdir -p "$TARGET_DIR/logs" "$TARGET_DIR/tmp"

if [[ ! -f "$TARGET_DIR/config.php" ]]; then
    sudo cp "$TARGET_DIR/config.example.php" "$TARGET_DIR/config.php"
fi

sudo chown -R "$USER":"$USER" "$TARGET_DIR"
APACHE_USER="$APACHE_USER" PROJECT_DIR="$TARGET_DIR" "$TARGET_DIR/scripts/fix-local-permissions.sh"

echo "Abre http://localhost/devpanel/setup.php para configurar la contraseña."
