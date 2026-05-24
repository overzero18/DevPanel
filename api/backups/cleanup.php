<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');
requirePermission('backups.delete');

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

$keep = (int) ($_POST['keep'] ?? 10);
$removed = devpanelCleanupBackups($keep);

logAction('backup_cleanup', 'Removed ' . count($removed) . ' backups, keep=' . $keep);

echo json_encode([
    'success' => true,
    'message' => count($removed) . ' backups borrados',
    'removed' => $removed,
]);
