<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');

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

$file = basename((string) ($_POST['file'] ?? ''));
$restoreAsNew = ($_POST['mode'] ?? '') === 'new';
$files = $_POST['files'] ?? [];
$files = is_array($files) ? $files : [$files];
$result = devpanelRestoreProjectBackup($file, $restoreAsNew, $files);

if (!$result)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo restaurar el backup']);
    exit;
}

logAction('backup_restore', $result['project'] . ' from ' . $file);

echo json_encode([
    'success' => true,
    'message' => 'Backup restaurado',
    'restore' => $result,
]);
