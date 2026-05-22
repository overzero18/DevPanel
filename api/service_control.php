<?php

require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/helpers/config.php';

header('Content-Type: application/json');

authenticateSession();

if ($_SERVER['REQUEST_METHOD'] !== 'POST')
{
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Método no permitido']);
    exit;
}

checkEndpointRateLimit('service_control', 10, 60);

if (!validateCsrfToken())
{
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token validation failed']);
    exit;
}

$service = $_POST['service'] ?? '';
$action = $_POST['action'] ?? '';

if (!validateService($service) || !validateAction($action))
{
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Parámetros inválidos']);
    exit;
}

$lamppBinary = rtrim(devpanelConfig('LAMPP_PATH'), DIRECTORY_SEPARATOR) . '/lampp';

$commands = [
    'apache' => [
        'start'   => 'sudo ' . $lamppBinary . ' startapache',
        'stop'    => 'sudo ' . $lamppBinary . ' stopapache',
        'restart' => 'sudo ' . $lamppBinary . ' restartapache'
    ],
    'mysql' => [
        'start'   => 'sudo ' . $lamppBinary . ' startmysql',
        'stop'    => 'sudo ' . $lamppBinary . ' stopmysql',
        'restart' => 'sudo ' . $lamppBinary . ' restartmysql'
    ]
];

$command = $commands[$service][$action];
$result = runControlledCommand($command);

logAction('service_control', "$service $action");

if ($result['exit_code'] !== 0)
{
    http_response_code(500);
    echo json_encode(['success' => false, 'output' => $result['output']]);
    exit;
}

echo json_encode(['success' => true, 'output' => $result['output']]);
