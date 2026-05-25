<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/plugins.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

echo json_encode([
    'success' => true,
    'plugins' => devpanelPublicPlugins(),
]);
