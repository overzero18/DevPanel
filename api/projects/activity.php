<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$path = $_GET['path'] ?? '';

if (!$path || !is_dir($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Proyecto no permitido']);
    exit;
}

$projectName = basename($path);

function devpanelTailFile(string $file, int $limit = 120): array
{
    if (!file_exists($file) || !is_readable($file))
    {
        return [];
    }

    $handle = fopen($file, 'rb');

    if ($handle === false)
    {
        return [];
    }

    $buffer = '';
    $chunkSize = 4096;
    $position = filesize($file);

    while ($position > 0 && substr_count($buffer, "\n") <= $limit)
    {
        $readSize = min($chunkSize, $position);
        $position -= $readSize;
        fseek($handle, $position);
        $buffer = fread($handle, $readSize) . $buffer;
    }

    fclose($handle);

    $lines = preg_split('/\R/', trim($buffer));

    return $lines === false ? [] : array_values(array_filter($lines));
}

function devpanelProjectActions(string $path, string $projectName): array
{
    $logFile = __DIR__ . '/../../logs/actions.log';
    $lines = array_reverse(devpanelTailFile($logFile, 250));
    $matches = [];

    foreach ($lines as $line)
    {
        if (stripos($line, $path) === false && stripos($line, $projectName) === false)
        {
            continue;
        }

        $matches[] = $line;

        if (count($matches) >= 8)
        {
            break;
        }
    }

    return $matches;
}

function devpanelProjectFiles(string $path): array
{
    $ignoredFolders = ['node_modules', '.git', 'vendor', 'tmp', 'logs'];
    $files = [];

    try
    {
        $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $filter = new RecursiveCallbackFilterIterator(
            $directory,
            static function ($current) use ($ignoredFolders) {
                if ($current->isDir() && in_array($current->getFilename(), $ignoredFolders, true))
                {
                    return false;
                }

                return true;
            }
        );

        foreach (new RecursiveIteratorIterator($filter) as $file)
        {
            if (!$file->isFile())
            {
                continue;
            }

            $files[] = [
                'name' => $file->getFilename(),
                'path' => str_replace($path . DIRECTORY_SEPARATOR, '', $file->getPathname()),
                'modified_at' => $file->getMTime(),
                'modified_label' => date('d/m/Y H:i', $file->getMTime()),
                'size' => formatearTamanoArchivo($file->getSize())
            ];
        }
    }
    catch (UnexpectedValueException $exception)
    {
        return [];
    }

    usort($files, static function ($a, $b) {
        return $b['modified_at'] <=> $a['modified_at'];
    });

    return array_slice($files, 0, 10);
}

function devpanelProjectCommits(string $path): array
{
    if (!is_dir($path . '/.git'))
    {
        return [];
    }

    $command = [
        'git',
        '-c',
        'safe.directory=' . $path,
        '-C',
        $path,
        'log',
        '--pretty=format:%h%x09%cr%x09%s',
        '-n',
        '6'
    ];

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
        return [];
    }

    $output = trim(stream_get_contents($pipes[1]));
    fclose($pipes[1]);
    fclose($pipes[2]);

    if (proc_close($process) !== 0 || $output === '')
    {
        return [];
    }

    return array_map(static function ($line) {
        $parts = explode("\t", $line, 3);

        return [
            'hash' => $parts[0] ?? '',
            'date' => $parts[1] ?? '',
            'message' => $parts[2] ?? ''
        ];
    }, preg_split('/\R/', $output) ?: []);
}

logAction('view_project_activity', $projectName);

echo json_encode([
    'success' => true,
    'project' => $projectName,
    'path' => $path,
    'actions' => devpanelProjectActions($path, $projectName),
    'files' => devpanelProjectFiles($path),
    'commits' => devpanelProjectCommits($path)
]);
