<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('terminal');

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'output' => 'Method not allowed']);
    exit;
}

checkEndpointRateLimit('terminal', 30, 60);

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'CSRF token validation failed']);
    exit;
}

$command = $_POST['command'] ?? '';
$command = trim($command);
$workingDirectory = $_POST['cwd'] ?? '';
$workingDirectory = trim($workingDirectory);

if ($command === '')
{
    echo json_encode(['success' => false, 'output' => '']);
    exit;
}

if (strlen($command) > 500)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'output' => 'Comando demasiado largo (máximo 500 caracteres)']);
    exit;
}

if (!validateCommand($command))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'Comando no permitido. Usa: ' . implode(', ', array_keys(getAllowedTerminalCommands()))]);
    exit;
}

$safeCommand = getSafeTerminalCommand($command);
$defaultWorkingDirectory = realpath(__DIR__ . '/..') ?: obtenerRutaBase();

if ($workingDirectory === '')
{
    $workingDirectory = $defaultWorkingDirectory;
}

$workingDirectory = realpath($workingDirectory);

if (!$workingDirectory || !is_dir($workingDirectory) || !validatePath($workingDirectory))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'output' => 'Ruta de trabajo no permitida']);
    exit;
}

if ($command === 'git status')
{
    $safeCommand = 'git -c safe.directory=' . escapeshellarg($workingDirectory) . ' status --short';
}

if ($command === 'git branch')
{
    $safeCommand = 'git -c safe.directory=' . escapeshellarg($workingDirectory) . ' branch';
}

$process = proc_open(
    $safeCommand,
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes,
    $workingDirectory,
    ['HOME' => sys_get_temp_dir()]
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'output' => 'No se pudo ejecutar el comando']);
    exit;
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);

fclose($pipes[1]);
fclose($pipes[2]);

$exitCode = proc_close($process);
$output .= $errorOutput;

logAction('execute_command', $command);

echo json_encode([
    'success' => $exitCode === 0,
    'output' => $output,
    'exit_code' => $exitCode,
]);
