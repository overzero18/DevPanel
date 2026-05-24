<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('backups');

$project = trim((string) ($_GET['project'] ?? ''));
$filePath = trim(str_replace('\\', '/', (string) ($_GET['file'] ?? '')));

if ($project === '' || $filePath === '' || str_starts_with($filePath, '/') || str_contains($filePath, '..'))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

$versions = [];

foreach (devpanelLoadBackups() as $backup)
{
    if (($backup['project'] ?? '') !== $project)
    {
        continue;
    }

    $zipPath = devpanelBackupsDir() . '/' . ($backup['file'] ?? '');

    if (!is_file($zipPath) || !class_exists('ZipArchive'))
    {
        continue;
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true)
    {
        continue;
    }

    $index = $zip->locateName($filePath);

    if ($index !== false)
    {
        $stat = $zip->statIndex($index) ?: [];
        $contents = $zip->getFromIndex($index);
        $versions[] = [
            'backup' => $backup['file'] ?? '',
            'created_at' => $backup['created_at'] ?? '',
            'size' => (int) ($stat['size'] ?? 0),
            'sha256' => $contents === false ? null : hash('sha256', $contents),
            'download' => '/devpanel/api/backups/download.php?file=' . rawurlencode($backup['file'] ?? ''),
        ];
    }

    $zip->close();
}

echo json_encode([
    'success' => true,
    'project' => $project,
    'file' => $filePath,
    'versions' => $versions,
]);
