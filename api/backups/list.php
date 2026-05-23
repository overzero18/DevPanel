<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/backups.php';

header('Content-Type: application/json');

authenticateSession();

echo json_encode(['success' => true, 'backups' => devpanelLoadBackups()]);
