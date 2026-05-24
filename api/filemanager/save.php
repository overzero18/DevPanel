<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('files');
requirePermission('files.write');

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
$content = $_POST['content'] ?? '';

if (!$path || !is_file($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Archivo no permitido']);
    exit;
}

$realPath = realpath($path);
$extension = strtolower(pathinfo($realPath, PATHINFO_EXTENSION));
$editableExtensions = ['txt', 'md', 'json', 'js', 'css', 'html', 'htm', 'xml', 'yml', 'yaml', 'ini', 'env', 'log', 'sql'];
$mime = mime_content_type($realPath) ?: 'application/octet-stream';

if (!str_starts_with($mime, 'text/') && !in_array($extension, $editableExtensions, true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Este tipo de archivo no se puede editar']);
    exit;
}

if (!is_writable($realPath))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos para guardar este archivo']);
    exit;
}

if (strlen($content) > 500 * 1024)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contenido demasiado grande']);
    exit;
}

if (file_put_contents($realPath, $content, LOCK_EX) === false)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el archivo']);
    exit;
}

logAction('filemanager_save', $realPath);

echo json_encode(['success' => true, 'message' => 'Archivo guardado']);
