<?php

require_once __DIR__ . '/../includes/security.php';

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

$commands = [
    'apache' => [
        'start'   => 'sudo /opt/lampp/lampp startapache',
        'stop'    => 'sudo /opt/lampp/lampp stopapache',
        'restart' => 'sudo /opt/lampp/lampp restartapache'
    ],
    'mysql' => [
        'start'   => 'sudo /opt/lampp/lampp startmysql',
        'stop'    => 'sudo /opt/lampp/lampp stopmysql',
        'restart' => 'sudo /opt/lampp/lampp restartmysql'
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
