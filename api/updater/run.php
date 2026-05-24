<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/updater.php';

authenticateSession();
requirePermission('settings');

header('Content-Type: application/json');

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

$status = devpanelUpdaterStatus();

if (!$status['can_update'])
{
    http_response_code(409);
    echo json_encode(['success' => false, 'message' => 'Updater bloqueado: configura upstream y deja git status limpio.']);
    exit;
}

$root = dirname(__DIR__, 2);
$process = proc_open(
    ['git', '-C', $root, 'pull', '--ff-only'],
    [1 => ['pipe', 'w'], 2 => ['pipe', 'w']],
    $pipes,
    null,
    ['HOME' => sys_get_temp_dir()]
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo ejecutar updater']);
    exit;
}

$output = stream_get_contents($pipes[1]) . stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exit = proc_close($process);

logAction('updater_run', $exit === 0 ? 'updated' : 'failed');

echo json_encode([
    'success' => $exit === 0,
    'message' => $exit === 0 ? 'DevPanel actualizado' : 'Git pull devolvió error',
    'output' => trim($output),
    'status' => devpanelUpdaterStatus(),
]);
