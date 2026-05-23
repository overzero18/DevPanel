<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/users.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$enabled = ($_POST['enabled'] ?? '0') === '1';
$secret = trim((string) devpanelConfig('DEVPANEL_2FA_SECRET', ''));

if ($enabled && $secret === '')
{
    $secret = devpanelGenerateBase32Secret();
}

if (!$enabled)
{
    $secret = '';
}

if (!devpanelWriteSecurityConfig([
    'DEVPANEL_2FA_ENABLED' => $enabled,
    'DEVPANEL_2FA_SECRET' => $secret,
]))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar 2FA']);
    exit;
}

logAction('two_factor_update', $enabled ? 'enabled' : 'disabled');

echo json_encode([
    'success' => true,
    'message' => $enabled ? '2FA activado' : '2FA desactivado',
    'two_factor' => [
        'enabled' => $enabled,
        'configured' => $secret !== '',
        'secret' => $secret,
        'issuer' => 'DevPanel',
        'account' => getCurrentUserName(),
    ],
]);
