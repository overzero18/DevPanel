<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

function devpanelPermissionItem(string $label, string $path, string $type, bool $needsWrite = false, ?string $hint = null): array
{
    $exists = file_exists($path);
    $isExpectedType = $type === 'file' ? is_file($path) : is_dir($path);
    $readable = $exists && is_readable($path);
    $writable = $exists && is_writable($path);
    $ok = $exists && $isExpectedType && $readable && (!$needsWrite || $writable);

    return [
        'label' => $label,
        'path' => $path,
        'type' => $type,
        'exists' => $exists,
        'readable' => $readable,
        'writable' => $writable,
        'needs_write' => $needsWrite,
        'ok' => $ok,
        'hint' => $hint
    ];
}

$configFile = __DIR__ . '/../config.php';
$items = [
    devpanelPermissionItem('Config local', $configFile, 'file', true, 'Permite guardar GitHub y rutas desde la UI.'),
    devpanelPermissionItem('htdocs', devpanelConfig('HTDOCS_PATH'), 'dir', true, 'Permite crear/clonar proyectos desde el panel.'),
    devpanelPermissionItem('Logs DevPanel', __DIR__ . '/../logs', 'dir', true, 'Permite registrar acciones del panel.'),
    devpanelPermissionItem('Tmp DevPanel', __DIR__ . '/../tmp', 'dir', true, 'Permite generar ZIP temporales.'),
    devpanelPermissionItem('PHP binary', devpanelConfig('PHP_BINARY'), 'file', false, 'Necesario para comandos PHP.'),
    devpanelPermissionItem('LAMPP', rtrim(devpanelConfig('LAMPP_PATH'), DIRECTORY_SEPARATOR) . '/lampp', 'file', false, 'Necesario para controlar Apache/MariaDB.'),
    devpanelPermissionItem('Apache error log', devpanelConfig('APACHE_ERROR_LOG'), 'file', false, 'Necesario para visor de logs.'),
    devpanelPermissionItem('Apache access log', devpanelConfig('APACHE_ACCESS_LOG'), 'file', false, 'Necesario para visor de logs.'),
    devpanelPermissionItem('PHP error log', devpanelConfig('PHP_ERROR_LOG'), 'file', false, 'Necesario para visor de logs.'),
    devpanelPermissionItem('MySQL data dir', devpanelConfig('MYSQL_DATA_DIR'), 'dir', false, 'Necesario para detectar logs MariaDB.')
];

$failed = array_values(array_filter($items, static fn ($item) => !$item['ok']));

echo json_encode([
    'success' => true,
    'items' => $items,
    'summary' => [
        'total' => count($items),
        'ok' => count($items) - count($failed),
        'failed' => count($failed)
    ]
]);
