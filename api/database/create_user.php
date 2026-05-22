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

$username = trim($_POST['username'] ?? '');
$password = (string) ($_POST['password'] ?? '');
$database = trim($_POST['database'] ?? '');

if (!preg_match('/^[a-zA-Z0-9_]{1,32}$/', $username))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario inválido']);
    exit;
}

if (strlen($password) < 8)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 8 caracteres']);
    exit;
}

if (!devpanelValidateDatabaseName($database) || in_array($database, devpanelSystemDatabases(), true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Base de datos inválida']);
    exit;
}

try
{
    $connection = devpanelDatabaseConnection();
    $userSafe = $connection->real_escape_string($username);
    $passwordSafe = $connection->real_escape_string($password);
    $databaseSafe = $connection->real_escape_string($database);

    $connection->query("CREATE USER IF NOT EXISTS `$userSafe`@'localhost' IDENTIFIED BY '$passwordSafe'");
    $connection->query("GRANT ALL PRIVILEGES ON `$databaseSafe`.* TO `$userSafe`@'localhost'");
    $connection->query('FLUSH PRIVILEGES');

    logAction('database_create_user', "$username on $database");

    echo json_encode(['success' => true, 'message' => 'Usuario creado y permisos asignados']);
}
catch (Throwable $exception)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear el usuario']);
}
