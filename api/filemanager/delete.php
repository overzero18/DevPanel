<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('files');
requirePermission('files.delete');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$path = $_POST['path'] ?? '';
$base = realpath(obtenerRutaBase());

if (!$path || !file_exists($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

$realPath = realpath($path);

if ($realPath === $base)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se puede borrar la carpeta base']);
    exit;
}

if (!is_writable(dirname($realPath)))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos para borrar este elemento']);
    exit;
}

function deleteFileManagerPath(string $path): bool
{
    if (is_file($path) || is_link($path))
    {
        return unlink($path);
    }

    if (!is_dir($path))
    {
        return false;
    }

    $items = scandir($path);

    if ($items === false)
    {
        return false;
    }

    foreach ($items as $item)
    {
        if ($item === '.' || $item === '..')
        {
            continue;
        }

        if (!deleteFileManagerPath($path . DIRECTORY_SEPARATOR . $item))
        {
            return false;
        }
    }

    return rmdir($path);
}

if (!deleteFileManagerPath($realPath))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar']);
    exit;
}

logAction('filemanager_delete', $realPath);

echo json_encode(['success' => true, 'message' => 'Elemento borrado']);
