<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/plugins.php';

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

$key = preg_replace('/[^a-z0-9_-]/', '', (string) ($_POST['key'] ?? ''));
$enabled = filter_var($_POST['enabled'] ?? false, FILTER_VALIDATE_BOOLEAN);
$catalog = devpanelPluginCatalog();

if (!isset($catalog[$key]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Plugin inválido']);
    exit;
}

$enabledKeys = devpanelEnabledPluginKeys();

if ($enabled)
{
    $enabledKeys[] = $key;
}
else
{
    $enabledKeys = array_values(array_filter($enabledKeys, static fn ($item) => $item !== $key));
}

if (!devpanelWritePluginConfig($enabledKeys))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar plugins']);
    exit;
}

logAction('plugin_toggle', $key . ':' . ($enabled ? 'enabled' : 'disabled'));

echo json_encode([
    'success' => true,
    'plugins' => devpanelPublicPlugins(),
]);
