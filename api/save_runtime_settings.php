<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

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

$configFile = __DIR__ . '/../config.php';
$config = file_exists($configFile) ? require $configFile : [];

if (!is_array($config))
{
    $config = [];
}

$fields = [
    'BASE_URL',
    'LOCALHOST_URL',
    'PHPMYADMIN_URL',
    'LAMPP_PATH',
    'HTDOCS_PATH',
    'PHP_BINARY',
    'APACHE_ERROR_LOG',
    'APACHE_ACCESS_LOG',
    'PHP_ERROR_LOG',
    'MYSQL_DATA_DIR',
    'MYSQL_HOST',
    'MYSQL_PORT',
    'MYSQL_USER',
    'MYSQL_PASSWORD',
    'DEVPANEL_DEMO_MODE',
    'DEVPANEL_MAINTENANCE_MODE',
    'DEVPANEL_MAINTENANCE_MESSAGE'
];

foreach ($fields as $field)
{
    $value = trim((string) ($_POST[strtolower($field)] ?? ''));

    if ($field === 'MYSQL_PORT')
    {
        $port = (int) $value;
        if ($port < 1 || $port > 65535)
        {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Puerto MySQL inválido']);
            exit;
        }

        $config[$field] = $port;
        continue;
    }

    if ($field === 'DEVPANEL_DEMO_MODE' || $field === 'DEVPANEL_MAINTENANCE_MODE')
    {
        $config[$field] = in_array(strtolower($value), ['1', 'true', 'yes', 'on', 'si', 'sí'], true);
        continue;
    }

    if ($value === '' && $field !== 'MYSQL_PASSWORD')
    {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => "$field no puede estar vacío"]);
        exit;
    }

    $config[$field] = $value;
}

if ((file_exists($configFile) && !is_writable($configFile)) || (!file_exists($configFile) && !is_writable(dirname($configFile))))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'config.php no tiene permisos de escritura']);
    exit;
}

$passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();

if (!$passwordHash || !devpanelWriteConfig($configFile, $passwordHash, $config))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar la configuración']);
    exit;
}

logAction('save_runtime_settings', 'Runtime settings updated');

echo json_encode(['success' => true, 'message' => 'Configuración guardada']);
