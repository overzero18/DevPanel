<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/users.php';

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

$name = trim((string) ($_POST['name'] ?? ''));
$config = devpanelUsersConfig();

if ($name === getCurrentUserName())
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No puedes borrar el usuario actual']);
    exit;
}

if (!isset($config['users'][$name]))
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Usuario no encontrado']);
    exit;
}

unset($config['users'][$name]);
$projectAccess = devpanelConfig('DEVPANEL_PROJECT_ACCESS', []);
$projectAccess = is_array($projectAccess) ? $projectAccess : [];
unset($projectAccess[$name]);

if (!devpanelWriteUsersConfig($config['users'], $config['roles']) || !devpanelWriteProjectAccessConfig($projectAccess))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar el usuario']);
    exit;
}

logAction('user_delete', $name);

echo json_encode(['success' => true, 'message' => 'Usuario eliminado']);
