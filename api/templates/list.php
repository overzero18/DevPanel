<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/project_templates.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

echo json_encode([
    'success' => true,
    'templates' => devpanelProjectTemplates(),
]);
