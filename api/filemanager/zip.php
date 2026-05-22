<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();

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

if (!class_exists('ZipArchive'))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'ZipArchive no está disponible']);
    exit;
}

$path = $_POST['path'] ?? '';

if (!$path || !file_exists($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

$realPath = realpath($path);
$tmpDir = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devpanel-filemanager';

if (!is_dir($tmpDir) && !mkdir($tmpDir, 0755, true))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar la carpeta temporal']);
    exit;
}

if (!is_writable($tmpDir))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos para generar ZIP en tmp']);
    exit;
}

$baseName = preg_replace('/[^a-zA-Z0-9._-]/', '_', basename($realPath));
$zipName = $baseName . '-' . date('Ymd-His') . '.zip';
$zipPath = $tmpDir . DIRECTORY_SEPARATOR . $zipName;
$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear el ZIP']);
    exit;
}

function addPathToFileManagerZip(ZipArchive $zip, string $path, string $basePath): void
{
    if (is_file($path))
    {
        $relative = ltrim(substr($path, strlen($basePath)), DIRECTORY_SEPARATOR);
        $zip->addFile($path, $relative !== '' ? $relative : basename($path));
        return;
    }

    $items = scandir($path);

    if ($items === false)
    {
        return;
    }

    foreach ($items as $item)
    {
        if ($item === '.' || $item === '..')
        {
            continue;
        }

        $child = $path . DIRECTORY_SEPARATOR . $item;
        $relative = ltrim(substr($child, strlen($basePath)), DIRECTORY_SEPARATOR);

        if (is_dir($child))
        {
            $zip->addEmptyDir($relative);
            addPathToFileManagerZip($zip, $child, $basePath);
            continue;
        }

        if (is_file($child))
        {
            $zip->addFile($child, $relative);
        }
    }
}

if (is_file($realPath))
{
    $zip->addFile($realPath, basename($realPath));
}
else
{
    $zip->addEmptyDir(basename($realPath));
    addPathToFileManagerZip($zip, $realPath, dirname($realPath));
}

$zip->close();

logAction('filemanager_zip', $realPath);

echo json_encode([
    'success' => true,
    'message' => 'ZIP generado',
    'download' => '/devpanel/api/filemanager/download_zip.php?file=' . rawurlencode($zipName)
]);
