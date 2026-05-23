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

function devpanelFindBackup(string $file): ?array
{
    if (!preg_match('/^[a-zA-Z0-9._-]+\.zip$/', $file))
    {
        return null;
    }

    foreach (devpanelLoadBackups() as $backup)
    {
        if (($backup['file'] ?? '') === $file)
        {
            return $backup;
        }
    }

    return null;
}

function devpanelPreviewProjectBackup(string $file, int $limit = 120): ?array
{
    if (!class_exists('ZipArchive'))
    {
        return null;
    }

    $backup = devpanelFindBackup($file);
    $zipPath = devpanelBackupsDir() . '/' . $file;

    if (!$backup || !is_file($zipPath))
    {
        return null;
    }

    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true)
    {
        return null;
    }

    $items = [];
    $totalSize = 0;

    for ($index = 0; $index < $zip->numFiles; $index++)
    {
        $stat = $zip->statIndex($index);

        if (!$stat)
        {
            continue;
        }

        $totalSize += (int) ($stat['size'] ?? 0);

        if (count($items) < $limit)
        {
            $items[] = [
                'name' => $stat['name'] ?? '',
                'size' => (int) ($stat['size'] ?? 0),
            ];
        }
    }

    $count = $zip->numFiles;
    $zip->close();

    return [
        'backup' => $backup,
        'files' => $items,
        'file_count' => $count,
        'total_size' => $totalSize,
        'truncated' => $count > $limit,
    ];
}

function devpanelRestoreProjectBackup(string $file, bool $restoreAsNew = false): ?array
{
    if (!class_exists('ZipArchive'))
    {
        return null;
    }

    $backup = devpanelFindBackup($file);

    if (!$backup)
    {
        return null;
    }

    $zipPath = devpanelBackupsDir() . '/' . $file;
    $originalPath = $backup['path'] ?? '';
    $targetPath = $originalPath;

    if (!is_file($zipPath) || !$originalPath || !esRutaPermitida(dirname($originalPath)))
    {
        return null;
    }

    if ($restoreAsNew)
    {
        $targetPath = rtrim(dirname($originalPath), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . basename($originalPath) . '_restored_' . date('Ymd_His');

        if (!mkdir($targetPath, 0755, true))
        {
            return null;
        }
    }
    elseif (!is_dir($targetPath) || !esRutaPermitida($targetPath))
    {
        return null;
    }

    $safetyBackup = $restoreAsNew ? null : devpanelCreateProjectBackup($targetPath);
    $zip = new ZipArchive();

    if ($zip->open($zipPath) !== true)
    {
        return null;
    }

    for ($index = 0; $index < $zip->numFiles; $index++)
    {
        $entry = $zip->getNameIndex($index);

        if (!$entry || str_starts_with($entry, '/') || str_contains($entry, '..'))
        {
            $zip->close();
            return null;
        }
    }

    $restoredFiles = 0;

    for ($index = 0; $index < $zip->numFiles; $index++)
    {
        $entry = $zip->getNameIndex($index);
        $destination = rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $entry;

        if (str_ends_with($entry, '/'))
        {
            if (!is_dir($destination) && !mkdir($destination, 0755, true))
            {
                $zip->close();
                return null;
            }

            continue;
        }

        if (!is_dir(dirname($destination)) && !mkdir(dirname($destination), 0755, true))
        {
            $zip->close();
            return null;
        }

        $contents = $zip->getFromIndex($index);

        if ($contents === false || file_put_contents($destination, $contents, LOCK_EX) === false)
        {
            $zip->close();
            return null;
        }

        $restoredFiles++;
    }

    $zip->close();

    return [
        'project' => $backup['project'] ?? basename($targetPath),
        'path' => $targetPath,
        'file' => $file,
        'restored_files' => $restoredFiles,
        'mode' => $restoreAsNew ? 'new_folder' : 'overwrite',
        'safety_backup' => $safetyBackup,
        'restored_at' => date('Y-m-d H:i:s'),
    ];
}
