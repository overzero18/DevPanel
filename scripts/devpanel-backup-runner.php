#!/usr/bin/env php
<?php

require_once __DIR__ . '/../includes/projects.php';
require_once __DIR__ . '/../includes/helpers/backups.php';

$target = $argv[1] ?? 'all';
$projects = getProjects();
$created = 0;

foreach ($projects as $project)
{
    $path = $project['path'] ?? '';

    if ($target !== 'all' && basename($path) !== $target && $path !== $target)
    {
        continue;
    }

    $backup = devpanelCreateProjectBackup($path);

    if ($backup)
    {
        $created++;
        echo "Backup creado: {$backup['file']}\n";
    }
}

if ($created === 0)
{
    fwrite(STDERR, "No se creó ningún backup.\n");
    exit(1);
}

echo "Backups creados: $created\n";
