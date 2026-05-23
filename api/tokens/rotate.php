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

$id = trim((string) ($_POST['id'] ?? ''));
$existing = $id !== '' ? devpanelFindApiToken($id) : null;
$rotated = $existing ? devpanelRotateApiToken($id) : null;

if (!$rotated)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se pudo rotar el token']);
    exit;
}

logAction('api_token_rotate', ($existing['name'] ?? 'token') . ':' . ($existing['role'] ?? 'viewer'));

echo json_encode([
    'success' => true,
    'message' => 'Token rotado',
    'token' => $rotated['token'],
    'item' => $rotated['item'],
]);
