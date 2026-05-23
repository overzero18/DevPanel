<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');

$file = basename((string) ($_GET['file'] ?? ''));
$preview = devpanelPreviewProjectBackup($file);

if (!$preview)
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Backup no encontrado']);
    exit;
}

echo json_encode(['success' => true, 'preview' => $preview]);
