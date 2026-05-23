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

$path = $_GET['path'] ?? '';

if (!$path || !is_file($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Archivo no permitido']);
    exit;
}

$realPath = realpath($path);
$size = filesize($realPath);
$mime = mime_content_type($realPath) ?: 'application/octet-stream';
$editableExtensions = ['txt', 'md', 'json', 'js', 'css', 'html', 'htm', 'xml', 'yml', 'yaml', 'ini', 'env', 'log', 'sql'];
$extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$isText = str_starts_with($mime, 'text/') || in_array($extension, $editableExtensions, true);
$maxPreviewSize = 200 * 1024;

if (str_starts_with($mime, 'image/'))
{
    echo json_encode([
        'success' => true,
        'mode' => 'image',
        'name' => basename($realPath),
        'path' => $realPath,
        'mime' => $mime,
        'size' => $size,
        'url' => '/devpanel/api/filemanager/download.php?path=' . rawurlencode($realPath)
    ]);
    exit;
}

if (!$isText)
{
    echo json_encode([
        'success' => true,
        'mode' => 'unsupported',
        'name' => basename($realPath),
        'path' => $realPath,
        'mime' => $mime,
        'size' => $size,
        'message' => 'Preview no disponible para este tipo de archivo'
    ]);
    exit;
}

if ($size > $maxPreviewSize)
{
    echo json_encode([
        'success' => true,
        'mode' => 'too_large',
        'name' => basename($realPath),
        'path' => $realPath,
        'mime' => $mime,
        'size' => $size,
        'message' => 'Archivo demasiado grande para preview'
    ]);
    exit;
}

$content = file_get_contents($realPath);

if ($content === false)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo leer el archivo']);
    exit;
}

echo json_encode([
    'success' => true,
    'mode' => 'text',
    'name' => basename($realPath),
    'path' => $realPath,
    'mime' => $mime,
    'size' => $size,
    'content' => $content
]);
