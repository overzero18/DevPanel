<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();

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

$path = trim((string) ($_POST['path'] ?? ''));
$backup = devpanelCreateProjectBackup($path);

if (!$backup)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear backup']);
    exit;
}

logAction('backup_create', $backup['project']);

echo json_encode(['success' => true, 'message' => 'Backup creado', 'backup' => $backup]);
