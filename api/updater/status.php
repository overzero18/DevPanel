<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/updater.php';

authenticateSession();
requirePermission('settings');

header('Content-Type: application/json');

echo json_encode(['success' => true, 'status' => devpanelUpdaterStatus()]);
