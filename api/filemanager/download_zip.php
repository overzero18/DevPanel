<?php

require_once __DIR__ . '/../../includes/security.php';

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$file = basename($_GET['file'] ?? '');

if (!preg_match('/^[a-zA-Z0-9._-]+\.zip$/', $file))
{
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ZIP inválido']);
    exit;
}

$zipPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'devpanel-filemanager' . DIRECTORY_SEPARATOR . $file;

if (!is_file($zipPath))
{
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'ZIP no encontrado']);
    exit;
}

header('Content-Type: application/zip');
header('Content-Length: ' . filesize($zipPath));
header('Content-Disposition: attachment; filename="' . rawurlencode($file) . '"');
header('X-Content-Type-Options: nosniff');

readfile($zipPath);
