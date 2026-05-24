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

if (!$path || !is_dir($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta no permitida']);
    exit;
}

if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Archivo inválido']);
    exit;
}

if (!is_writable($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'No hay permisos de escritura en esta carpeta']);
    exit;
}

$originalName = basename($_FILES['file']['name']);
$extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
$blockedExtensions = ['php', 'php3', 'php4', 'php5', 'phtml', 'phar', 'cgi', 'pl', 'py', 'sh'];

if (!preg_match('/^[a-zA-Z0-9._ -]{1,160}$/', $originalName) || $originalName === '.' || $originalName === '..')
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre de archivo inválido']);
    exit;
}

if (in_array($extension, $blockedExtensions, true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Tipo de archivo no permitido']);
    exit;
}

$target = rtrim($path, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $originalName;

if (file_exists($target))
{
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'El archivo ya existe']);
    exit;
}

if (!move_uploaded_file($_FILES['file']['tmp_name'], $target))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo subir el archivo']);
    exit;
}

logAction('filemanager_upload', $target);

echo json_encode(['success' => true, 'message' => 'Archivo subido']);
