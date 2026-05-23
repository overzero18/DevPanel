<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/users.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$secret = devpanelConfig('DEVPANEL_2FA_SECRET', '');

echo json_encode([
    'success' => true,
    'two_factor' => [
        'enabled' => (bool) devpanelConfig('DEVPANEL_2FA_ENABLED', false),
        'configured' => $secret !== '',
        'secret' => $secret,
        'issuer' => 'DevPanel',
        'account' => getCurrentUserName(),
    ],
    'api_tokens' => devpanelPublicApiTokens(),
]);
