<?php

require_once __DIR__ . '/../../includes/security.php';

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

$name = trim((string) ($_POST['name'] ?? ''));
$action = trim((string) ($_POST['action'] ?? ''));
$allowedActions = ['start', 'stop', 'restart', 'logs'];

if (!preg_match('/^[a-zA-Z0-9_.-]{1,128}$/', $name))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Contenedor inválido']);
    exit;
}

if (!in_array($action, $allowedActions, true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Acción Docker inválida']);
    exit;
}

$dockerBinary = trim(shell_exec('command -v docker') ?? '');

if ($dockerBinary === '')
{
    echo json_encode(['success' => false, 'message' => 'Docker no está instalado o no está en PATH']);
    exit;
}

$command = $action === 'logs'
    ? [$dockerBinary, 'logs', '--tail', '120', $name]
    : [$dockerBinary, $action, $name];

$process = proc_open(
    $command,
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes,
    null,
    ['HOME' => sys_get_temp_dir()]
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo ejecutar Docker']);
    exit;
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

logAction('docker_action', "$action $name");

echo json_encode([
    'success' => $exitCode === 0,
    'message' => $exitCode === 0 ? 'Docker ejecutado' : 'Docker devolvió error',
    'output' => trim($output . $errorOutput)
]);
