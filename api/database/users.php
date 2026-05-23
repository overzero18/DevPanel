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
        "SELECT User AS user, Host AS host
         FROM mysql.user
         ORDER BY User, Host"
    );

    $users = [];

    while ($row = $result->fetch_assoc())
    {
        $users[] = [
            'user' => $row['user'],
            'host' => $row['host'],
            'system' => in_array($row['user'], ['root', 'mysql.sys', 'mysql.session', 'mysql.infoschema', 'pma'], true)
        ];
    }

    echo json_encode(['success' => true, 'users' => $users]);
}
catch (Throwable $exception)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudieron cargar usuarios MariaDB']);
}
