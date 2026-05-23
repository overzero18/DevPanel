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
$expiresDays = (int) ($_POST['expires_days'] ?? 30);
$created = devpanelCreateApiToken($name, $role, $expiresDays);

if (!$created)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se pudo crear el token']);
    exit;
}

logAction('api_token_create', $created['item']['name'] . ':' . $role);

echo json_encode([
    'success' => true,
    'message' => 'Token creado',
    'token' => $created['token'],
    'item' => $created['item'],
]);
