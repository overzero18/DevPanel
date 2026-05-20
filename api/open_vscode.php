<?php

require_once __DIR__ . '/../includes/security.php';

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

$command = 'sudo /usr/local/bin/devpanel-open-vscode ' . escapeshellarg($path);

shell_exec($command);

logAction('open_vscode', $path);

echo json_encode(['success' => true]);