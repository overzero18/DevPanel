<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/local_domains.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('domains');

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

$domain = strtolower(trim((string) ($_POST['domain'] ?? '')));
$result = devpanelApplyDomain($domain);

if ($result['success'])
{
    logAction('local_domain_apply', $domain);
}

echo json_encode($result);
