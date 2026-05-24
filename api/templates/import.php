<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/project_templates.php';

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

$payload = json_decode((string) ($_POST['template'] ?? ''), true);
$result = is_array($payload) ? devpanelImportProjectTemplate($payload) : null;

if (!$result)
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Plantilla inválida']);
    exit;
}

logAction('project_template_import', $result[0]);

echo json_encode([
    'success' => true,
    'message' => 'Plantilla importada',
    'key' => $result[0],
    'template' => $result[1],
]);
