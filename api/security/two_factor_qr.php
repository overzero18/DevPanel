<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/qr.php';

authenticateSession();
requirePermission('settings');

$secret = devpanelConfig('DEVPANEL_2FA_SECRET', '');
$issuer = 'DevPanel';
$account = getCurrentUserName();
$uri = $secret !== ''
    ? 'otpauth://totp/' . rawurlencode($issuer . ':' . $account) . '?secret=' . rawurlencode($secret) . '&issuer=' . rawurlencode($issuer)
    : '';

header('Content-Type: image/svg+xml; charset=UTF-8');

if ($uri === '')
{
    echo '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="220"><rect width="100%" height="100%" fill="#111827"/><text x="50%" y="50%" fill="#94a3b8" text-anchor="middle" font-family="sans-serif" font-size="13">2FA inactivo</text></svg>';
    exit;
}

$qrencode = trim(shell_exec('command -v qrencode') ?? '');

if ($qrencode === '')
{
    $svg = devpanelQrSvg($uri);

    echo $svg ?: '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="220"><rect width="100%" height="100%" fill="#111827"/><text x="50%" y="48%" fill="#94a3b8" text-anchor="middle" font-family="sans-serif" font-size="13">URI demasiado largo</text><text x="50%" y="58%" fill="#64748b" text-anchor="middle" font-family="sans-serif" font-size="11">Copia el URI manual</text></svg>';
    exit;
}

$process = proc_open(
    [$qrencode, '-t', 'SVG', '-o', '-', $uri],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes
);

if (!is_resource($process))
{
    http_response_code(500);
    exit;
}

$svg = stream_get_contents($pipes[1]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);

echo $exit === 0 && $svg !== ''
    ? $svg
    : '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="220"><rect width="100%" height="100%" fill="#111827"/><text x="50%" y="50%" fill="#ef4444" text-anchor="middle" font-family="sans-serif" font-size="13">Error QR</text></svg>';
