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

if (!devpanelValidateDatabaseName($name))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Nombre de base de datos inválido']);
    exit;
}

try
{
    $connection = devpanelDatabaseConnection();
    $connection->query('CREATE DATABASE `' . $connection->real_escape_string($name) . '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci');

    logAction('database_create', $name);

    echo json_encode(['success' => true, 'message' => 'Base de datos creada']);
}
catch (Throwable $exception)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear la base de datos']);
}
