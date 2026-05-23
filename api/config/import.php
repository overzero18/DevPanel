<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/config.php';

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

if (empty($_FILES['config']['tmp_name']) || !is_uploaded_file($_FILES['config']['tmp_name']))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Archivo inválido']);
    exit;
}

$payload = json_decode((string) file_get_contents($_FILES['config']['tmp_name']), true);

if (!is_array($payload))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'JSON inválido']);
    exit;
}

$configFile = dirname(__DIR__, 2) . '/config.php';
$config = is_file($configFile) ? require $configFile : [];
$config = is_array($config) ? $config : [];
$runtime = is_array($payload['runtime'] ?? null) ? $payload['runtime'] : [];
$allowedRuntime = array_diff_key(devpanelDefaultRuntimeConfig(), array_flip([
    'MYSQL_PASSWORD',
    'GITHUB_USER',
    'GITHUB_REPO',
    'GITHUB_REMOTE_URL',
]));

foreach (array_intersect_key($runtime, $allowedRuntime) as $key => $value)
{
    $config[$key] = $key === 'MYSQL_PORT' ? (int) $value : $value;
}

if (isset($payload['theme']) && is_string($payload['theme']))
{
    $config['THEME'] = $payload['theme'];
}

if (isset($payload['available_themes']) && is_array($payload['available_themes']))
{
    $config['AVAILABLE_THEMES'] = array_values(array_filter($payload['available_themes'], 'is_string'));
}

if (isset($payload['roles']) && is_array($payload['roles']))
{
    $config['DEVPANEL_ROLES'] = $payload['roles'];
}

$passwordHash = $config['DEVPANEL_PASSWORD'] ?? getConfigPassword();

if (!$passwordHash || !devpanelWriteConfig($configFile, $passwordHash, $config))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo importar configuración']);
    exit;
}

logAction('config_import', 'Public config imported');

echo json_encode(['success' => true, 'message' => 'Configuración importada sin secretos']);
