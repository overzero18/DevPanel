<?php

require_once __DIR__ . '/../includes/security.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'GET')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$type = $_GET['type'] ?? 'apache';

$logs = ['apache' => '/opt/lampp/logs/error_log'];

if (!isset($logs[$type]))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Log inválido']);
    exit;
}

$file = $logs[$type];

if (!file_exists($file))
{
    http_response_code(404);
    echo json_encode(['success' => false, 'message' => 'Log no encontrado']);
    exit;
}

$lines = file($file, FILE_IGNORE_NEW_LINES);

if ($lines === false)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'No se pudo leer el log']);
    exit;
}

$content = implode("\n", array_slice($lines, -50));
$content = escapeHtml($content);

logAction('view_logs', "Viewed $type logs");

echo json_encode(['success' => true, 'content' => $content]);
