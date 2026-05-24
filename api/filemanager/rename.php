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
$name = trim($_POST['name'] ?? '');

if (!$path || !file_exists($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._ -]{1,160}$/', $name) || $name === '.' || $name === '..')
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre inválido']);
    exit;
}

$realPath = realpath($path);
$parent = dirname($realPath);
$target = $parent . DIRECTORY_SEPARATOR . $name;

if (!is_writable($parent))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos para renombrar en esta carpeta']);
    exit;
}

if (file_exists($target))
{
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Ya existe un elemento con ese nombre']);
    exit;
}

if (!validatePath($parent) || !rename($realPath, $target))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo renombrar']);
    exit;
}

logAction('filemanager_rename', "$realPath -> $target");

echo json_encode(['success' => true, 'message' => 'Elemento renombrado']);
