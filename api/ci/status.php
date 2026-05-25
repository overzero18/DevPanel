<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/ci.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('settings');

echo json_encode(array_merge(devpanelCiLocalStatus(), [
    'remote' => devpanelCiRemoteRuns(5),
]));
