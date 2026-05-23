<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/local_domains.php';

header('Content-Type: application/json');

authenticateSession();
requirePermission('domains');

$domain = strtolower(trim((string) ($_GET['domain'] ?? '')));
$item = devpanelFindDomain($domain);

if (!$item || !devpanelValidateDomain($domain))
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Dominio no encontrado']);
    exit;
}

$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'timeout' => 3,
        'ignore_errors' => true,
    ],
]);

$body = @file_get_contents('http://' . $domain, false, $context);
$headers = $http_response_header ?? [];
$status = 0;

foreach ($headers as $header)
{
    if (preg_match('/^HTTP\/\S+\s+(\d+)/', $header, $matches))
    {
        $status = (int) $matches[1];
        break;
    }
}

echo json_encode([
    'success' => $body !== false && $status >= 200 && $status < 500,
    'message' => $body !== false ? 'Dominio responde' : 'El dominio no responde todavía',
    'status' => $status,
    'domain' => $domain,
]);
