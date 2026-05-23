<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('files');

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

if (!$path || !is_dir($path))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Ruta inválida']);
    exit;
}

if (!validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

$projectName = basename($path);
$tmpDir = __DIR__ . '/../tmp';

if (!is_dir($tmpDir))
{
    mkdir($tmpDir, 0755, true);
}

$zipPath = $tmpDir . '/' . $projectName . '.zip';

function hasExcludedPathSegment($relativePath)
{
    $excluded = ['node_modules', '.git', 'vendor', 'tmp'];
    $segments = array_filter(explode('/', str_replace('\\', '/', $relativePath)), 'strlen');

    return count(array_intersect($segments, $excluded)) > 0;
}

if (file_exists($zipPath))
{
    unlink($zipPath);
}

$zip = new ZipArchive();

if ($zip->open($zipPath, ZipArchive::CREATE) !== TRUE)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear ZIP']);
    exit;
}

$files = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::LEAVES_ONLY
);

$directories = new RecursiveIteratorIterator(
    new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS),
    RecursiveIteratorIterator::SELF_FIRST
);

foreach ($directories as $dir)
{
    if ($dir->isDir())
    {
        $dirPath = $dir->getRealPath();
        $relativePath = substr($dirPath, strlen($path) + 1);

        if (hasExcludedPathSegment($relativePath)) {
            continue;
        }

        if (!empty($relativePath))
        {
            $zip->addEmptyDir($relativePath);
        }
    }
}

foreach ($files as $file)
{
    if (!$file->isDir())
    {
        $filePath = $file->getRealPath();

        if (!$filePath)
        {
            continue;
        }

        $relativePath = substr($filePath, strlen($path) + 1);

        if (hasExcludedPathSegment($relativePath)) {
            continue;
        }

        $zip->addFile($filePath, $relativePath);
    }
}

if ($zip->numFiles === 0)
{
    $zip->close();
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'ZIP vacío']);
    exit;
}

$zip->close();

if (!file_exists($zipPath))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se generó el ZIP']);
    exit;
}

$baseUrl = rtrim(devpanelConfig('BASE_URL', '/devpanel'), '/');

logAction('generate_zip', "Generated ZIP for: $projectName");

echo json_encode(['success' => true, 'download' => $baseUrl . '/tmp/' . rawurlencode($projectName) . '.zip']);
