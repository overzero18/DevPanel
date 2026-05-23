<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('files');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$path = $_GET['path'] ?? obtenerRutaBase();
$depth = max(1, min(3, (int) ($_GET['depth'] ?? 2)));

if (!$path || !is_dir($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

function devpanelBuildFolderTree(string $path, int $depth): array
{
    $children = [];
    $items = scandir($path);

    if ($items === false || $depth <= 0)
    {
        return $children;
    }

    foreach ($items as $item)
    {
        if ($item === '.' || $item === '..' || str_starts_with($item, '.'))
        {
            continue;
        }

        $child = $path . DIRECTORY_SEPARATOR . $item;

        if (!is_dir($child) || !esRutaPermitida($child))
        {
            continue;
        }

        $children[] = [
            'name' => $item,
            'path' => realpath($child) ?: $child,
            'children' => devpanelBuildFolderTree($child, $depth - 1)
        ];
    }

    usort($children, static function ($a, $b) {
        return strcasecmp($a['name'], $b['name']);
    });

    return $children;
}

$realPath = realpath($path) ?: $path;

echo json_encode([
    'success' => true,
    'root' => [
        'name' => basename($realPath) ?: $realPath,
        'path' => $realPath,
        'children' => devpanelBuildFolderTree($realPath, $depth)
    ]
]);
