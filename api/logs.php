<?php

header('Content-Type: application/json');

$type = $_GET['type'] ?? 'apache';

$logs = [

    'apache' => '/opt/lampp/logs/error_log',

];

if (!isset($logs[$type]))
{
    echo json_encode([

        'success' => false,
        'message' => 'Log inválido'

    ]);

    exit;
}

$file = $logs[$type];

if (!file_exists($file))
{
    echo json_encode([

        'success' => false,
        'message' => 'Log no encontrado'

    ]);

    exit;
}

$content = shell_exec(
    'tail -n 50 ' . escapeshellarg($file)
);

echo json_encode([

    'success' => true,
    'content' => $content

]);