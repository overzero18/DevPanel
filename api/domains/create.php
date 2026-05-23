<?php

require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/helpers/filesystem.php';
require_once __DIR__ . '/../../includes/helpers/local_domains.php';

header('Content-Type: application/json');

authenticateSession();

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
$path = trim((string) ($_POST['path'] ?? ''));

if (!devpanelValidateDomain($domain))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dominio inválido. Usa algo como proyecto.test']);
    exit;
}

if (!$path || !is_dir($path) || !validatePath($path))
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Ruta de proyecto no permitida']);
    exit;
}

$item = devpanelUpsertDomain($domain, $path);

if (!$item)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo preparar el dominio local']);
    exit;
}

logAction('local_domain_create', "$domain => $path");

echo json_encode([
    'success' => true,
    'message' => 'Dominio local preparado',
    'domain' => $item,
]);
