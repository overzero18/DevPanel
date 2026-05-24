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

$path = $_POST['path'] ?? obtenerRutaBase();
$name = trim($_POST['name'] ?? '');

if (!$path || !is_dir($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

if (!preg_match('/^[a-zA-Z0-9._ -]{1,160}$/', $name) || $name === '.' || $name === '..')
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo inválido']);
    exit;
}

if (!is_writable($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos de escritura en esta carpeta']);
    exit;
}

$target = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $name;

if (file_exists($target))
{
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'El archivo ya existe']);
    exit;
}

if (file_put_contents($target, '', LOCK_EX) === false)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear el archivo']);
    exit;
}

logAction('filemanager_create_file', $target);

echo json_encode(['success' => true, 'message' => 'Archivo creado']);
