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
$confirmation = trim($_POST['confirmation'] ?? '');

if (!devpanelValidateDatabaseName($name) || in_array($name, devpanelSystemDatabases(), true))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Base de datos inválida']);
    exit;
}

if ($confirmation !== $name)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La confirmación no coincide']);
    exit;
}

try
{
    $connection = devpanelDatabaseConnection();
    $connection->query('DROP DATABASE `' . $connection->real_escape_string($name) . '`');

    logAction('database_delete', $name);

    echo json_encode(['success' => true, 'message' => 'Base de datos borrada']);
}
catch (Throwable $exception)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar la base de datos']);
}
