<?php

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {

    echo json_encode([
        'success' => false,
        'message' => 'Método no permitido'
    ]);

    exit;
}

$service = $_POST['service'] ?? '';
$action = $_POST['action'] ?? '';

$allowedServices = ['apache', 'mysql'];
$allowedActions = ['start', 'stop', 'restart'];

if (
    !in_array($service, $allowedServices) ||
    !in_array($action, $allowedActions)
) {

    echo json_encode([
        'success' => false,
        'message' => 'Parámetros inválidos'
    ]);

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

$output = shell_exec($command . ' 2>&1');

echo json_encode([

    'success' => true,
    'output' => $output

]);