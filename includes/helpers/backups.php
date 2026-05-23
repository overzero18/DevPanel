<?php

require_once __DIR__ . '/filesystem.php';

function devpanelBackupsDir(): string
{
    return dirname(__DIR__, 2) . '/tmp/backups';
}

function devpanelBackupIndexFile(): string
{
    return dirname(__DIR__, 2) . '/logs/backups.json';
}

function devpanelLoadBackups(): array
{
    $file = devpanelBackupIndexFile();

    if (!file_exists($file))
    {
        return [];
    }

    $items = json_decode((string) file_get_contents($file), true);

    return is_array($items) ? $items : [];
}

function devpanelSaveBackups(array $items): bool
{
    $file = devpanelBackupIndexFile();

    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true))
    {
        return false;
    }

    return file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function devpanelCreateProjectBackup(string $path): ?array
{
    if (!class_exists('ZipArchive') || !is_dir($path) || !esRutaPermitida($path))
    {
        return null;
    }

    $backupDir = devpanelBackupsDir();

    if (!is_dir($backupDir) && !mkdir($backupDir, 0755, true))
    {
        return null;
    }

    $project = basename($path);
    $fileName = preg_replace('/[^a-zA-Z0-9._-]/', '_', $project) . '-' . date('Ymd-His') . '.zip';
    $zipPath = $backupDir . '/' . $fileName;
    $zip = new ZipArchive();

    if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
    {
        return null;
    }

    $ignored = ['.git', 'node_modules', 'vendor', 'tmp', 'logs'];
    $baseLength = strlen(rtrim($path, DIRECTORY_SEPARATOR)) + 1;

    $iterator = new RecursiveIteratorIterator(
        new RecursiveCallbackFilterIterator(
            new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
            static function ($current) use ($ignored) {
                return !$current->isDir() || !in_array($current->getFilename(), $ignored, true);
            }
        )
    );

    foreach ($iterator as $file)
    {
        if ($file->isFile())
        {
            $zip->addFile($file->getPathname(), substr($file->getPathname(), $baseLength));
        }
    }

    $zip->close();

    $item = [
        'id' => hash('sha256', $zipPath),
        'project' => $project,
        'path' => $path,
        'file' => $fileName,
        'size' => filesize($zipPath),
        'created_at' => date('Y-m-d H:i:s'),
        'download' => '/devpanel/api/backups/download.php?file=' . rawurlencode($fileName),
    ];

    $items = devpanelLoadBackups();
    array_unshift($items, $item);
    devpanelSaveBackups(array_slice($items, 0, 50));

    return $item;
}
