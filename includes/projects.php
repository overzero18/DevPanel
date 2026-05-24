<?php

require_once __DIR__ . '/helpers/config.php';
require_once __DIR__ . '/helpers/demo.php';
require_once __DIR__ . '/helpers/users.php';

function getProjectType($path)
{
    if (file_exists($path . '/artisan'))
    {
        return 'Laravel';
    }

    if (file_exists($path . '/wp-config.php'))
    {
        return 'WordPress';
    }

    if (file_exists($path . '/index.php'))
    {
        return 'PHP';
    }

    if (file_exists($path . '/package.json'))
    {
        return 'Node';
    }

    if (file_exists($path . '/composer.json'))
    {
        return 'Composer';
    }

    if (file_exists($path . '/index.html'))
    {
        return 'Static';
    }

    return 'Desconocido';
}

function getProjectModifiedAt($path)
{
    return getProjectFilesystemStats($path)['modified_at'];
}

function getProjectFilesystemStats($path)
{
    $size = 0;
    $modifiedAt = filemtime($path) ?: 0;
    $ignoredFolders = ['node_modules', '.git', 'vendor', 'tmp', 'logs'];

    try
    {
        $directory = new RecursiveDirectoryIterator(
            $path,
            RecursiveDirectoryIterator::SKIP_DOTS
        );

        $filter = new RecursiveCallbackFilterIterator(
            $directory,
            function ($current) use ($ignoredFolders) {
                if ($current->isDir() && in_array($current->getFilename(), $ignoredFolders, true))
                {
                    return false;
                }

                return true;
            }
        );

        foreach (new RecursiveIteratorIterator($filter) as $file)
        {
            $modifiedAt = max($modifiedAt, $file->getMTime());

            if ($file->isFile())
            {
                $size += $file->getSize();
            }
        }
    }
    catch (UnexpectedValueException $exception)
    {
        return [
            'size' => 0,
            'modified_at' => $modifiedAt
        ];
    }

    return [
        'size' => round($size / 1024 / 1024, 2),
        'modified_at' => $modifiedAt
    ];
}

function formatProjectModifiedAt($timestamp)
{
    if (!$timestamp)
    {
        return 'Sin fecha';
    }

    return date('d/m/Y H:i', $timestamp);
}

function getFolderSize($path)
{
    return getProjectFilesystemStats($path)['size'];
}

function runProjectGitCommand($path, $args)
{
    if (!is_dir($path . '/.git'))
    {
        return null;
    }

    $command = array_merge(['git', '-c', 'safe.directory=' . $path, '-C', $path], $args);
    $process = proc_open(
        $command,
        [
            1 => ['pipe', 'w'],
            2 => ['pipe', 'w']
        ],
        $pipes,
        null,
        ['HOME' => sys_get_temp_dir()]
    );

    if (!is_resource($process))
    {
        return null;
    }

    $output = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);

    return proc_close($process) === 0 ? $output : null;
}

function getProjectGitInfo($path)
{
    if (!is_dir($path . '/.git'))
    {
        return [
            'enabled' => false,
            'branch' => null,
            'dirty' => false,
            'changes' => 0
        ];
    }

    $branch = runProjectGitCommand($path, ['branch', '--show-current']);
    $status = runProjectGitCommand($path, ['status', '--short']);
    $changes = $status === null || $status === ''
        ? 0
        : count(preg_split('/\R/', $status));

    return [
        'enabled' => true,
        'branch' => $branch ?: 'detached',
        'dirty' => $changes > 0,
        'changes' => $changes
    ];
}

function getProjects()
{
    if (devpanelDemoModeEnabled())
    {
        return devpanelDemoProjects();
    }

    $projectsPath = devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');
    $localhostUrl = rtrim(devpanelConfig('LOCALHOST_URL', 'http://localhost'), '/');

    $folders = scandir($projectsPath);

    $ignoredFolders = array_merge(['.', '..', 'img'], devpanelConfig('EXCLUDED_PROJECT_FOLDERS', []));

    $projects = [];
    $devpanelPath = realpath(__DIR__ . '/..');
    $htdocsRealPath = realpath($projectsPath);

    if ($devpanelPath && $htdocsRealPath && str_starts_with($devpanelPath, $htdocsRealPath . DIRECTORY_SEPARATOR))
    {
        $devpanelFolder = basename($devpanelPath);
        $stats = getProjectFilesystemStats($devpanelPath);

        $projects[] = [
            'name' => 'DevPanel',
            'path' => $devpanelPath,
            'url' => $localhostUrl . '/' . rawurlencode($devpanelFolder),
            'type' => 'PHP',
            'size' => $stats['size'],
            'modified_at' => $stats['modified_at'],
            'modified_label' => formatProjectModifiedAt($stats['modified_at']),
            'writable' => is_writable($devpanelPath),
            'internal' => true,
            'git' => getProjectGitInfo($devpanelPath)
        ];
    }

    foreach ($folders as $folder)
    {
        if (in_array($folder, $ignoredFolders))
        {
            continue;
        }

        $fullPath = $projectsPath . '/' . $folder;

        if (is_dir($fullPath))
        {
            $stats = getProjectFilesystemStats($fullPath);
            $modifiedAt = $stats['modified_at'];

            $projects[] = [

                'name' => $folder,

                'path' => $fullPath,

                'url' => $localhostUrl . '/' . rawurlencode($folder),

                'type' => getProjectType($fullPath),

                'size' => $stats['size'],

                'modified_at' => $modifiedAt,

                'modified_label' => formatProjectModifiedAt($modifiedAt),

                'writable' => is_writable($fullPath),

                'internal' => false,

                'git' => getProjectGitInfo($fullPath)

            ];
        }
    }

    usort($projects, function ($a, $b) {
        return $b['modified_at'] <=> $a['modified_at'];
    });

    return array_values(array_filter($projects, static function ($project) {
        return devpanelUserCanAccessProject($project['name'], $project['path']);
    }));
}
