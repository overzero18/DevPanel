<?php

require_once __DIR__ . '/helpers/config.php';

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

    return 'Desconocido';
}

function getFolderSize($path)
{
    $size = 0;
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
            if ($file->isFile())
            {
                $size += $file->getSize();
            }
        }
    }
    catch (UnexpectedValueException $exception)
    {
        return 0;
    }

    return round($size / 1024 / 1024, 2);
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
    $projectsPath = devpanelConfig('HTDOCS_PATH', '/opt/lampp/htdocs');
    $localhostUrl = rtrim(devpanelConfig('LOCALHOST_URL', 'http://localhost'), '/');

    $folders = scandir($projectsPath);

    $ignoredFolders = array_merge(['.', '..', 'img'], devpanelConfig('EXCLUDED_PROJECT_FOLDERS', []));

    $projects = [];

    foreach ($folders as $folder)
    {
        if (in_array($folder, $ignoredFolders))
        {
            continue;
        }

        $fullPath = $projectsPath . '/' . $folder;

        if (is_dir($fullPath))
        {
            $projects[] = [

                'name' => $folder,

                'path' => $fullPath,

                'url' => $localhostUrl . '/' . rawurlencode($folder),

                'type' => getProjectType($fullPath),

                'size' => getFolderSize($fullPath),

                'git' => getProjectGitInfo($fullPath)

            ];
        }
    }

    return $projects;
}
