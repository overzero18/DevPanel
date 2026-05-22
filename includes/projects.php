<?php

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

function getProjects()
{
    $projectsPath = '/opt/lampp/htdocs';

    $folders = scandir($projectsPath);

    $ignoredFolders = [

        '.',
        '..',

        'img',
        'webalizer',
        'dashboard',
        'xampp',
        'phpmyadmin'

    ];

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

                'url' => 'http://localhost/' . $folder,

                'type' => getProjectType($fullPath),

                'size' => getFolderSize($fullPath)

            ];
        }
    }

    return $projects;
}
