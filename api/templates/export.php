<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/project_templates.php';

authenticateSession();
requirePermission('settings');

$key = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) ($_GET['key'] ?? ''));
$templates = devpanelProjectTemplates();

if ($key === '' || empty($templates[$key]['custom']))
{
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Plantilla no exportable']);
    exit;
}

header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="devpanel-template-' . $key . '.json"');
echo json_encode(array_merge(['key' => $key], $templates[$key]), JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
