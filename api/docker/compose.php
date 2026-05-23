<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('docker');

$method = $_SERVER['REQUEST_METHOD'];
$docker = trim(shell_exec('command -v docker') ?? '');
$composeAvailable = $docker !== '';

function devpanelFindComposeFiles(): array
{
    $base = obtenerRutaBase();
    $items = [];
    $docker = trim(shell_exec('command -v docker') ?? '');

    foreach (glob($base . '/*/{docker-compose.yml,docker-compose.yaml,compose.yml,compose.yaml}', GLOB_BRACE) ?: [] as $file)
    {
        if (!is_file($file) || !validatePath($file))
        {
            continue;
        }

        $items[] = [
            'project' => basename(dirname($file)),
            'path' => $file,
            'directory' => dirname($file),
            'services' => $docker ? devpanelComposeServices($docker, $file) : [],
        ];
    }

    return $items;
}

function devpanelComposeServices(string $docker, string $file): array
{
    $process = proc_open(
        [$docker, 'compose', '-f', $file, 'config', '--services'],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname($file),
        ['HOME' => sys_get_temp_dir()]
    );

    if (!is_resource($process))
    {
        return [];
    }

    $output = stream_get_contents($pipes[1]);
    fclose($pipes[1]);
    fclose($pipes[2]);
    $exit = proc_close($process);

    if ($exit !== 0)
    {
        return [];
    }

    return array_values(array_filter(array_map('trim', preg_split('/\R/', $output) ?: [])));
}

if ($method === 'GET')
{
    echo json_encode([
        'success' => true,
        'available' => $composeAvailable,
        'files' => devpanelFindComposeFiles(),
        'message' => $composeAvailable ? null : 'Docker no está instalado o no está en PATH',
    ]);
    exit;
}

if ($method !== 'POST')
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

$path = trim((string) ($_POST['path'] ?? ''));
$action = trim((string) ($_POST['action'] ?? ''));
$service = trim((string) ($_POST['service'] ?? ''));
$allowed = [
    'up' => ['docker', 'compose', '-f', $path, 'up', '-d'],
    'down' => ['docker', 'compose', '-f', $path, 'down'],
    'ps' => ['docker', 'compose', '-f', $path, 'ps'],
    'logs' => ['docker', 'compose', '-f', $path, 'logs', '--tail', '120'],
    'restart' => ['docker', 'compose', '-f', $path, 'restart'],
];

if (!$composeAvailable)
{
    echo json_encode(['success' => false, 'message' => 'Docker no está disponible']);
    exit;
}

if (!$path || !is_file($path) || !validatePath($path) || !isset($allowed[$action]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compose o acción inválida']);
    exit;
}

$command = $allowed[$action];
$command[0] = $docker;

if ($service !== '')
{
    if (!preg_match('/^[a-zA-Z0-9_.-]{1,80}$/', $service))
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Servicio inválido']);
        exit;
    }

    $command[] = $service;
}

$process = proc_open(
    $command,
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    dirname($path),
    ['HOME' => sys_get_temp_dir()]
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo ejecutar Docker Compose']);
    exit;
}

$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);

logAction('docker_compose', trim("$action $service $path"));

echo json_encode([
    'success' => $exit === 0,
    'message' => $exit === 0 ? 'Docker Compose ejecutado' : 'Docker Compose devolvió error',
    'output' => trim($output),
]);
