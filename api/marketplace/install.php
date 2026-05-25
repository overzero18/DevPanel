<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/plugins.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true) ?? [];
$repository = $data['repository'] ?? null;

if (!$repository)
{
    http_response_code(400);
    echo json_encode(['error' => 'Missing repository URL']);
    exit;
}

if (!filter_var($repository, FILTER_VALIDATE_URL))
{
    http_response_code(400);
    echo json_encode(['error' => 'Invalid repository URL']);
    exit;
}

$result = devpanelPluginInstall($repository);
auditLog('settings', $result['success'] ? 'plugin_installed' : 'plugin_install_failed', $result['plugin_id'] ?? '');

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
