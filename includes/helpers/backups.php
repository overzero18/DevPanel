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

function devpanelBackupSchedulesFile(): string
{
    return dirname(__DIR__, 2) . '/logs/backup_schedules.json';
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

function devpanelLoadBackupSchedules(): array
{
    $file = devpanelBackupSchedulesFile();

    if (!file_exists($file))
    {
        return [];
    }

    $items = json_decode((string) file_get_contents($file), true);

    return is_array($items) ? $items : [];
}

function devpanelSaveBackupSchedules(array $items): bool
{
    $file = devpanelBackupSchedulesFile();

    if (!is_dir(dirname($file)) && !mkdir(dirname($file), 0755, true))
    {
        return false;
    }

    return file_put_contents($file, json_encode(array_values($items), JSON_PRETTY_PRINT), LOCK_EX) !== false;
}

function devpanelBackupScheduleIntervalSeconds(string $frequency): int
{
    return match ($frequency) {
        'hourly' => 3600,
        'weekly' => 604800,
        default => 86400,
    };
}

function devpanelSaveBackupSchedule(string $path, string $frequency, bool $enabled = true): ?array
{
    if (!is_dir($path) || !esRutaPermitida($path))
    {
        return null;
    }

    if (!in_array($frequency, ['hourly', 'daily', 'weekly'], true))
    {
        return null;
    }

    $items = devpanelLoadBackupSchedules();
    $id = hash('sha256', $path);
    $now = date('Y-m-d H:i:s');
    $existing = null;

    foreach ($items as $index => $item)
    {
        if (($item['id'] ?? '') === $id)
        {
            $existing = $item;
            unset($items[$index]);
            break;
        }
    }

    $schedule = array_merge($existing ?: [], [
        'id' => $id,
        'project' => basename($path),
        'path' => $path,
        'frequency' => $frequency,
        'enabled' => $enabled,
        'updated_at' => $now,
        'created_at' => $existing['created_at'] ?? $now,
        'last_run_at' => $existing['last_run_at'] ?? null,
        'last_file' => $existing['last_file'] ?? null,
        'history' => $existing['history'] ?? [],
    ]);

    array_unshift($items, $schedule);

    return devpanelSaveBackupSchedules($items) ? $schedule : null;
}

function devpanelDeleteBackupSchedule(string $id): bool
{
    $items = array_values(array_filter(
        devpanelLoadBackupSchedules(),
        static fn ($item) => ($item['id'] ?? '') !== $id
    ));

    return devpanelSaveBackupSchedules($items);
}

function devpanelFindBackupSchedule(string $id): ?array
{
    foreach (devpanelLoadBackupSchedules() as $item)
    {
        if (($item['id'] ?? '') === $id)
        {
            return $item;
        }
    }

    return null;
}

function devpanelRecordBackupScheduleRun(array $schedule, array $backup, int $timestamp): array
{
    $history = $schedule['history'] ?? [];
    array_unshift($history, [
        'file' => $backup['file'] ?? '',
        'size' => $backup['size'] ?? 0,
        'created_at' => $backup['created_at'] ?? date('Y-m-d H:i:s', $timestamp),
        'download' => $backup['download'] ?? '',
    ]);

    $schedule['last_run_at'] = date('Y-m-d H:i:s', $timestamp);
    $schedule['last_file'] = $backup['file'] ?? null;
    $schedule['history'] = array_slice($history, 0, 8);

    return $schedule;
}

function devpanelRunBackupScheduleNow(string $id): ?array
{
    $items = devpanelLoadBackupSchedules();
    $now = time();

    foreach ($items as $index => $item)
    {
        if (($item['id'] ?? '') !== $id || empty($item['path']) || !is_dir($item['path']))
        {
            continue;
        }

        $backup = devpanelCreateProjectBackup($item['path']);

        if (!$backup)
        {
            return null;
        }

        $items[$index] = devpanelRecordBackupScheduleRun($item, $backup, $now);
        devpanelSaveBackupSchedules($items);

        return [
            'schedule' => $items[$index],
            'backup' => $backup,
        ];
    }

    return null;
}

function devpanelRunDueBackupSchedules(): array
{
    $items = devpanelLoadBackupSchedules();
    $created = [];
    $now = time();

    foreach ($items as &$item)
    {
        if (empty($item['enabled']) || empty($item['path']) || !is_dir($item['path']))
        {
            continue;
        }

        $lastRun = !empty($item['last_run_at']) ? strtotime($item['last_run_at']) : 0;
        $interval = devpanelBackupScheduleIntervalSeconds($item['frequency'] ?? 'daily');

        if ($lastRun && ($now - $lastRun) < $interval)
        {
            continue;
        }

        $backup = devpanelCreateProjectBackup($item['path']);

        if ($backup)
        {
            $item = devpanelRecordBackupScheduleRun($item, $backup, $now);
            $created[] = $backup;
        }
    }

    unset($item);
    devpanelSaveBackupSchedules($items);

    return $created;
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

function devpanelDeleteBackupFile(string $file): bool
{
    $backup = devpanelFindBackup($file);
    $path = devpanelBackupsDir() . '/' . $file;

    if (!$backup || !is_file($path))
    {
        return false;
    }

    if (!unlink($path))
    {
        return false;
    }

    $items = array_values(array_filter(
        devpanelLoadBackups(),
        static fn ($item) => ($item['file'] ?? '') !== $file
    ));

    $schedules = devpanelLoadBackupSchedules();
    foreach ($schedules as &$schedule)
    {
        if (($schedule['last_file'] ?? '') === $file)
        {
            $schedule['last_file'] = null;
        }

        $schedule['history'] = array_values(array_filter(
            $schedule['history'] ?? [],
            static fn ($item) => ($item['file'] ?? '') !== $file
        ));
    }
    unset($schedule);

    devpanelSaveBackups($items);
    devpanelSaveBackupSchedules($schedules);

    return true;
}

function devpanelCleanupBackups(int $keep = 10): array
{
    $keep = max(1, min(100, $keep));
    $items = devpanelLoadBackups();
    $removed = [];

    usort($items, static function ($a, $b) {
        return strcmp($b['created_at'] ?? '', $a['created_at'] ?? '');
    });

    $keepFiles = array_flip(array_column(array_slice($items, 0, $keep), 'file'));

    foreach ($items as $item)
    {
        $file = $item['file'] ?? '';

        if ($file === '' || isset($keepFiles[$file]))
        {
            continue;
        }

        if (devpanelDeleteBackupFile($file))
        {
            $removed[] = $file;
        }
    }

    return $removed;
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
    $targetPath = $backup['path'] ?? '';
    $summary = [
        'same_hash' => 0,
        'different_hash' => 0,
        'missing' => 0,
        'directories' => 0,
    ];

    for ($index = 0; $index < $zip->numFiles; $index++)
    {
        $stat = $zip->statIndex($index);

        if (!$stat)
        {
            continue;
        }

        $totalSize += (int) ($stat['size'] ?? 0);
        $name = $stat['name'] ?? '';

        if (str_ends_with($name, '/'))
        {
            $summary['directories']++;
            continue;
        }

        $currentFile = $targetPath ? rtrim($targetPath, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name : '';
        $currentState = 'missing';
        $backupHash = null;
        $currentHash = null;

        if ($currentFile && is_file($currentFile))
        {
            $contents = $zip->getFromIndex($index);
            $backupHash = $contents === false ? null : hash('sha256', $contents);
            $currentHash = hash_file('sha256', $currentFile) ?: null;
            $currentState = ($backupHash && $currentHash && hash_equals($backupHash, $currentHash))
                ? 'same_hash'
                : 'different_hash';
        }

        $summary[$currentState] = ($summary[$currentState] ?? 0) + 1;

        if (count($items) < $limit)
        {
            $items[] = [
                'name' => $name,
                'size' => (int) ($stat['size'] ?? 0),
                'current_state' => $currentState,
                'backup_hash' => $backupHash ? substr($backupHash, 0, 12) : null,
                'current_hash' => $currentHash ? substr($currentHash, 0, 12) : null,
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
        'diff_summary' => $summary,
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
