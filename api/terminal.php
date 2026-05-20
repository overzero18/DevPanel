<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'output' => 'Method not allowed']);
    exit;
}

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'CSRF token validation failed']);
    exit;
}

$command = $_POST['command'] ?? '';
$command = trim($command);

if ($command === '')
{
    echo json_encode(['success' => false, 'output' => '']);
    exit;
}

if (!validateCommand($command))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'Comando no permitido']);
    exit;
}

$output = shell_exec($command . ' 2>&1');

logAction('execute_command', $command);

echo json_encode(['success' => true, 'output' => $output]);