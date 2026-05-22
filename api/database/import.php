<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/database.php';

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

$name = trim($_POST['name'] ?? '');

if (!devpanelValidateDatabaseName($name) || in_array($name, devpanelSystemDatabases(), true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Base de datos inválida']);
    exit;
}

if (!isset($_FILES['sql_file']) || $_FILES['sql_file']['error'] !== UPLOAD_ERR_OK)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Archivo SQL inválido']);
    exit;
}

$fileName = $_FILES['sql_file']['name'];
$extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

if ($extension !== 'sql')
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Solo se permiten archivos .sql']);
    exit;
}

if ($_FILES['sql_file']['size'] > 50 * 1024 * 1024)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Archivo SQL demasiado grande']);
    exit;
}

$mysqlBinary = rtrim(devpanelConfig('LAMPP_PATH', '/opt/lampp'), DIRECTORY_SEPARATOR) . '/bin/mysql';

if (!is_executable($mysqlBinary))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'mysql no está disponible']);
    exit;
}

$command = [
    $mysqlBinary,
    '--host=' . devpanelConfig('MYSQL_HOST', '127.0.0.1'),
    '--port=' . (string) devpanelConfig('MYSQL_PORT', 3306),
    '--user=' . devpanelConfig('MYSQL_USER', 'root'),
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
        0 => ['file', $_FILES['sql_file']['tmp_name'], 'r'],
        1 => ['pipe', 'w'],
        2 => ['pipe', 'w']
    ],
    $pipes
);

if (!is_resource($process))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo iniciar importación']);
    exit;
}

$output = stream_get_contents($pipes[1]);
$errorOutput = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($exitCode !== 0)
{
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'No se pudo importar SQL',
        'output' => trim($output . $errorOutput)
    ]);
    exit;
}

logAction('database_import', $name);

echo json_encode(['success' => true, 'message' => 'SQL importado correctamente']);
