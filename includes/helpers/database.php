<?php

require_once __DIR__ . '/config.php';

function devpanelDatabaseConnection(): mysqli
{
    mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

    $connection = new mysqli(
        devpanelConfig('MYSQL_HOST', '127.0.0.1'),
        devpanelConfig('MYSQL_USER', 'root'),
        devpanelConfig('MYSQL_PASSWORD', ''),
        '',
        (int) devpanelConfig('MYSQL_PORT', 3306)
    );

    $connection->set_charset('utf8mb4');

    return $connection;
}

function devpanelValidateDatabaseName(string $name): bool
{
    return preg_match('/^[a-zA-Z0-9_]{1,64}$/', $name) === 1;
}

function devpanelSystemDatabases(): array
{
    return ['information_schema', 'mysql', 'performance_schema', 'phpmyadmin', 'test'];
}
