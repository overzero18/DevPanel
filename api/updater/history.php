<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/state.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('git');

echo json_encode([
    'success' => true,
    'history' => devpanelStateUpdaterHistory(),
    'status' => devpanelUpdaterStatus(),
]);
