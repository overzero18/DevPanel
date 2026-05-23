<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/database.php';

authenticateSession();
requirePermission('database');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$name = trim($_GET['name'] ?? '');

if (!devpanelValidateDatabaseName($name) || in_array($name, devpanelSystemDatabases(), true))
{
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Base de datos inválida']);
    exit;
}

$dumpBinary = rtrim(devpanelConfig('LAMPP_PATH', '/opt/lampp'), DIRECTORY_SEPARATOR) . '/bin/mysqldump';

if (!is_executable($dumpBinary))
{
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'mysqldump no está disponible']);
    exit;
}

$command = [
    $dumpBinary,
    '--host=' . devpanelConfig('MYSQL_HOST', '127.0.0.1'),
    '--port=' . (string) devpanelConfig('MYSQL_PORT', 3306),
    '--user=' . devpanelConfig('MYSQL_USER', 'root'),
    '--single-transaction',
    '--routines',
    '--triggers',
    $name
];

$password = devpanelConfig('MYSQL_PASSWORD', '');

if ($password !== '')
{
    array_splice($command, 4, 0, ['--password=' . $password]);
}

$process = proc_open(
    $command,
    [
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes
);

if (!is_resource($process))
{
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'No se pudo iniciar exportación']);
    exit;
}

header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="' . rawurlencode($name . '-' . date('Ymd-His') . '.sql') . '"');
header('X-Content-Type-Options: nosniff');

fpassthru($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0)
{
    error_log($errorOutput);
}

logAction('database_export', $name);
