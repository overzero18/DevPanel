<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

authenticateSession();
requirePermission('files');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$path = $_GET['path'] ?? '';

if (!$path || !is_file($path) || !validatePath($path))
{
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Archivo no permitido']);
    exit;
}

$realPath = realpath($path);

if (!$realPath)
{
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Archivo no encontrado']);
    exit;
}

logAction('filemanager_download', $realPath);

header('Content-Type: application/octet-stream');
header('Content-Length: ' . filesize($realPath));
header('Content-Disposition: attachment; filename="' . rawurlencode(basename($realPath)) . '"');
header('X-Content-Type-Options: nosniff');

readfile($realPath);
