<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

authenticateSession();
requirePermission('backups');

$file = basename((string) ($_GET['file'] ?? ''));

if (!preg_match('/^[a-zA-Z0-9._-]+\.zip$/', $file))
{
    http_response_code(400);
    echo 'Archivo inválido';
    exit;
}

$path = devpanelBackupsDir() . '/' . $file;

if (!is_file($path))
{
    http_response_code(404);
    echo 'Backup no encontrado';
    exit;
}

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($path));
header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
header('X-Content-Type-Options: nosniff');

readfile($path);
