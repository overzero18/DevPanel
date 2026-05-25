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
$pluginId = $data['plugin_id'] ?? null;

if (!$pluginId)
{
    http_response_code(400);
    echo json_encode(['error' => 'Missing plugin ID']);
    exit;
}

$result = devpanelPluginUninstall($pluginId);
auditLog('settings', $result['success'] ? 'plugin_uninstalled' : 'plugin_uninstall_failed', $pluginId);

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
