<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/demo.php';

authenticateSession();

header('Content-Type: application/json');

echo json_encode([
    'success' => true,
    'enabled' => devpanelDemoModeEnabled(),
    'projects' => devpanelDemoProjects(),
]);
