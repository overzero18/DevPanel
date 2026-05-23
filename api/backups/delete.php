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

if (!preg_match('/^[a-zA-Z0-9._-]+\.zip$/', $file) || !devpanelDeleteBackupFile($file))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar backup']);
    exit;
}

logAction('backup_delete', $file);

echo json_encode(['success' => true, 'message' => 'Backup borrado']);
