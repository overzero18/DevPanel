<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/database.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('database');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

try
{
    $connection = devpanelDatabaseConnection();
    $result = $connection->query(
        "SELECT SCHEMA_NAME AS name
         FROM information_schema.SCHEMATA
         ORDER BY SCHEMA_NAME"
    );

    $databases = [];
    $systemDatabases = devpanelSystemDatabases();

    while ($row = $result->fetch_assoc())
    {
        $name = $row['name'];
        $tablesResult = $connection->query(
            "SELECT COUNT(*) AS total
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = '" . $connection->real_escape_string($name) . "'"
        );
        $tables = (int) ($tablesResult->fetch_assoc()['total'] ?? 0);

        $databases[] = [
            'name' => $name,
            'tables' => $tables,
            'system' => in_array($name, $systemDatabases, true)
        ];
    }

    echo json_encode(['success' => true, 'databases' => $databases]);
}
catch (Throwable $exception)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo conectar a MariaDB']);
}
