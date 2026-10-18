<?php

header('Content-Type: application/json');

require_once '../../includes/security.php';
require_once '../../includes/helpers/filesystem.php';

authenticateSession();
requirePermission('files');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$path = $_GET['path'] ?? obtenerRutaBase();

if (!validatePath($path))
{
    echo json_encode([

        'success' => false,

        'message' => 'Ruta no permitida'

    ]);

    exit;
}

if (!is_dir($path))
{
    echo json_encode([

        'success' => false,

        'message' => 'Directorio no válido'

    ]);

    exit;
}

$items = listarArchivosDirectorio($path);
$currentPath = realpath($path);
$basePath = realpath(obtenerRutaBase());

if ($currentPath && $basePath && $currentPath === $basePath && function_exists('devpanelUserCanAccessPath'))
{
    $items = array_values(array_filter($items, static function (array $item): bool {
        return devpanelUserCanAccessPath($item['path']);
    }));
}
$breadcrumbs = [];

if ($currentPath && $basePath)
{
    $relative = trim(substr($currentPath, strlen($basePath)), DIRECTORY_SEPARATOR);
    $breadcrumbs[] = [
        'name' => basename($basePath),
        'path' => $basePath
    ];

    if ($relative !== '')
    {
        $runningPath = $basePath;
        foreach (explode(DIRECTORY_SEPARATOR, $relative) as $segment)
        {
            $runningPath .= DIRECTORY_SEPARATOR . $segment;
            $breadcrumbs[] = [
                'name' => $segment,
                'path' => $runningPath
            ];
        }
    }
}

echo json_encode([

    'success' => true,

    'currentPath' => $currentPath,

    'basePath' => $basePath,

    'parentPath' => $currentPath !== $basePath ? dirname($currentPath) : null,

    'writable' => is_writable($currentPath),

    'breadcrumbs' => $breadcrumbs,

    'items' => $items

]);
