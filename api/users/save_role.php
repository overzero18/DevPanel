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
$permissions = $_POST['permissions'] ?? [];
$config = devpanelUsersConfig();

if (!devpanelValidateUserName($name))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rol inválido']);
    exit;
}

if ($name === 'admin')
{
    $permissions = ['*'];
}
elseif (!is_array($permissions))
{
    $permissions = [];
}

$config['roles'][$name] = devpanelNormalizePermissions($permissions);

if (!$config['roles'][$name] || !devpanelWriteUsersConfig($config['users'], $config['roles']))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el rol']);
    exit;
}

logAction('role_save', $name);

echo json_encode(['success' => true, 'message' => 'Rol guardado']);
