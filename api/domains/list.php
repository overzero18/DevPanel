<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/local_domains.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('domains');

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

echo json_encode(['success' => true, 'domains' => devpanelLoadDomains()]);
