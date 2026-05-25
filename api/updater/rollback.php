<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/updater.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('git');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$result = devpanelUpdaterRollback();
auditLog('git', $result['success'] ? 'rollback_panel' : 'rollback_panel_failed', $result['message'] ?? '');

http_response_code($result['success'] ? 200 : 400);
echo json_encode($result);
