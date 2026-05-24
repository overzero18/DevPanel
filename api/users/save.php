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
$role = trim((string) ($_POST['role'] ?? 'viewer'));
$password = (string) ($_POST['password'] ?? '');
$config = devpanelUsersConfig();
$projectAccess = devpanelConfig('DEVPANEL_PROJECT_ACCESS', []);
$projectAccess = is_array($projectAccess) ? $projectAccess : [];

if (!devpanelValidateUserName($name))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Usuario inválido']);
    exit;
}

if (!isset($config['roles'][$role]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Rol inválido']);
    exit;
}

if (!isset($config['users'][$name]) && strlen($password) < 12)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 12 caracteres']);
    exit;
}

if ($password !== '' && strlen($password) < 12)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'La contraseña debe tener al menos 12 caracteres']);
    exit;
}

$existing = $config['users'][$name] ?? [];
$config['users'][$name] = [
    'password' => $password !== ''
        ? password_hash($password, PASSWORD_BCRYPT, ['cost' => 10])
        : ($existing['password'] ?? ''),
    'role' => $role,
];

if (!$config['users'][$name]['password'] || !devpanelWriteUsersConfig($config['users'], $config['roles']))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar el usuario']);
    exit;
}

$projects = $_POST['projects'] ?? ['*'];
$projects = is_array($projects) ? $projects : [$projects];
$projectAccess[$name] = array_values(array_unique(array_filter(array_map('trim', $projects))));

if (!$projectAccess[$name])
{
    $projectAccess[$name] = ['*'];
}

if (!devpanelWriteProjectAccessConfig($projectAccess))
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo guardar acceso a proyectos']);
    exit;
}

logAction('user_save', $name . ':' . $role);

echo json_encode(['success' => true, 'message' => 'Usuario guardado']);
