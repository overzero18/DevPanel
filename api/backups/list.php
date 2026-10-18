<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');

$backups = array_values(array_filter(devpanelLoadBackups(), static function (array $backup): bool {
    return devpanelBackupPathAllowed((string) ($backup['path'] ?? ''));
}));

echo json_encode(['success' => true, 'backups' => $backups]);
