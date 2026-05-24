<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('docker');

$path = trim((string) ($_REQUEST['path'] ?? ''));

if (!$path || !is_file($path) || !validatePath($path) || !preg_match('/(^|\/)(docker-compose|compose)\.ya?ml$/', $path))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compose inválido']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET')
{
    echo json_encode([
        'success' => true,
        'path' => $path,
        'content' => file_get_contents($path),
        'writable' => is_writable($path),
    ]);
    exit;
}

requirePermission('docker.actions');
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

$content = (string) ($_POST['content'] ?? '');
$mode = (string) ($_POST['mode'] ?? 'validate');

if (strlen($content) > 512 * 1024)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Compose demasiado grande']);
    exit;
}

$tmp = tempnam(sys_get_temp_dir(), 'devpanel-compose-');
file_put_contents($tmp, $content, LOCK_EX);
$docker = trim(shell_exec('command -v docker') ?? '');
$valid = false;
$output = '';

if ($docker !== '')
{
    $process = proc_open(
        [$docker, 'compose', '-f', $tmp, 'config'],
        [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
        $pipes,
        dirname($path),
        ['HOME' => sys_get_temp_dir()]
    );

    if (is_resource($process))
    {
        $output = trim(stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]));
        fclose($pipes[1]);
        fclose($pipes[2]);
        $valid = proc_close($process) === 0;
    }
}
else
{
    $valid = preg_match('/(^|\n)\s*services\s*:/', $content) === 1;
    $output = $valid ? 'Validación básica OK. Docker no está instalado.' : 'Falta services:.';
}

@unlink($tmp);

if (!$valid)
{
    echo json_encode(['success' => false, 'message' => 'Compose inválido', 'output' => $output]);
    exit;
}

if ($mode === 'save')
{
    if (!is_writable($path) || file_put_contents($path, $content, LOCK_EX) === false)
    {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'No se pudo guardar compose']);
        exit;
    }

    logAction('docker_compose_save', $path);
}

echo json_encode([
    'success' => true,
    'message' => $mode === 'save' ? 'Compose guardado' : 'Compose válido',
    'output' => $output,
]);
