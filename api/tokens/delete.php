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

if ($id === '' || !devpanelDeleteApiToken($id))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No se pudo borrar el token']);
    exit;
}

logAction('api_token_delete', $id);

echo json_encode(['success' => true, 'message' => 'Token borrado']);
