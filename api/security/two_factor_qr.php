<?php

require_once __DIR__ . '/../../includes/security.php';

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
    $hash = hash('sha256', $uri);
    $cells = '';
    $size = 25;
    $cell = 7;

    for ($y = 0; $y < $size; $y++)
    {
        for ($x = 0; $x < $size; $x++)
        {
            $finder = ($x < 7 && $y < 7) || ($x > 17 && $y < 7) || ($x < 7 && $y > 17);
            $bit = hexdec($hash[($x + ($y * $size)) % strlen($hash)]) % 2 === 0;

            if ($finder || $bit)
            {
                $cells .= '<rect x="' . (22 + ($x * $cell)) . '" y="' . (16 + ($y * $cell)) . '" width="' . $cell . '" height="' . $cell . '" fill="#111827"/>';
            }
        }
    }

    echo '<svg xmlns="http://www.w3.org/2000/svg" width="220" height="240" viewBox="0 0 220 240"><rect width="100%" height="100%" fill="#fff"/>' . $cells . '<text x="110" y="216" fill="#334155" text-anchor="middle" font-family="sans-serif" font-size="10">Fallback local: copia el URI si tu app no lo escanea</text></svg>';
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
